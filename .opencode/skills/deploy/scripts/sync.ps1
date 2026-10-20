<#
.SYNOPSIS
  Despliegue de Carto al directorio WAMP con reporte por paso.
.DESCRIPTION
  Preflight, respaldo, build, git-sync, sync con robocopy, composer,
  migraciones, caches, storage:link, reinicio de Apache y smoke test.
  Emite una linea por paso:
    STEP|nombre|OK|1234ms|detalle
  y termina con SUMMARY|ok|fallidos|ms_total. En -DryRun solo preflight.
.EXAMPLE
  .\sync.ps1 -DryRun
  .\sync.ps1 -Target "C:\wamp64\www\carto" -BaseUrl "http://localhost:8080"
#>
[CmdletBinding()]
param(
    [string]$Source = "D:\progra\carto",
    [string]$Target = "C:\wamp64\www\carto",
    [string]$BaseUrl = "http://localhost:8080",
    [switch]$DryRun,
    [switch]$SkipBuild,
    [switch]$NoMaintenance,
    [switch]$SkipGitSync,
    [string]$ApacheService = "wampapache64"
)

$ErrorActionPreference = "Stop"
$script:failed = 0
$script:totalMs = 0
$script:warnings = @()

function Step([string]$name, [scriptblock]$body, [ref]$out) {
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    try {
        $detail = & $body
        if ($detail -is [array]) { $detail = ($detail | Out-String).Trim() }
        $sw.Stop()
        $script:totalMs += $sw.ElapsedMilliseconds
        Write-Output ("STEP|{0}|OK|{1}ms|{2}" -f $name, $sw.ElapsedMilliseconds, $detail)
        if ($out) { $out.Value = $detail }
    } catch {
        $sw.Stop()
        $script:totalMs += $sw.ElapsedMilliseconds
        $script:failed += 1
        Write-Output ("STEP|{0}|FAIL|{1}ms|{2}" -f $name, $sw.ElapsedMilliseconds, $_.Exception.Message)
        throw
    }
}

function Warn-Step([string]$message) {
    $script:warnings += $message
    Write-Output "WARN|$message"
}

function Find-WampPhp() {
    $found = Get-ChildItem -Path "C:\wamp64\bin\php" -Directory -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -match "^php8\.3" } |
        Sort-Object Name -Descending | Select-Object -First 1
    if ($null -eq $found) { throw "PHP 8.3 del WAMP no encontrado en C:\wamp64\bin\php" }
    $php = Join-Path $found.FullName "php.exe"
    if (-not (Test-Path -LiteralPath $php)) { throw "No existe $php" }
    return $php
}

function Dotenv-Value([string]$file, [string]$key) {
    $line = Get-Content -LiteralPath $file | Where-Object { $_ -match ("^\s*" + $key + "\s*=") } | Select-Object -First 1
    if (-not $line) { return $null }
    return ($line -replace ("^\s*" + $key + "\s*=\s*"), "").Trim().Trim('"').Trim("'")
}

function Git([string[]]$gitArgs) {
    $out = & git @gitArgs 2>&1 | Out-String
    if ($LASTEXITCODE -ne 0) { throw "git $($gitArgs -join ' ') fallo: $($out.Trim())" }
    return $out.Trim()
}

try {
    Step "preflight-git" {
        $st = Git @("-C", $Source, "status", "--short")
        if ($st) { throw "Hay cambios sin commitear en origen" }
        "limpio"
    }

    Step "preflight-pushed" {
        $head = (Git @("-C", $Source, "rev-parse", "HEAD")).Trim()
        try {
            $remote = (Git @("-C", $Source, "ls-remote", "origin", "refs/heads/main")).Trim()
            if (-not $remote) { throw "origin/main no responde" }
            $remoteSha = ($remote -split "`t")[0]
            if ($remoteSha -ne $head) {
                Warn-Step "El commit $head aun no esta en origin/main ($remoteSha). El despliegue no queda respaldado en remoto."
                "no pusheado"
            } else {
                "pusheado"
            }
        } catch {
            Warn-Step "No se pudo consultar origin/main: $($_.Exception.Message). Se despliega igual."
            "remoto inaccesible"
        }
    }

    $wampPhp = ""
    Step "preflight-php" {
        $p = Find-WampPhp
        $v = & $p -v | Select-Object -First 1
        "$p ($v)"
    } ([ref]$wampPhp)
    $wampPhp = ($wampPhp -split " \(")[0]

    Step "preflight-env" {
        $env = Join-Path $Target ".env"
        if (-not (Test-Path -LiteralPath $env)) { throw "Falta .env en destino (no se crea solo)" }
        if ((Dotenv-Value $env "APP_DEBUG") -eq "true") { throw "APP_DEBUG=true en destino" }
        ".env presente, debug off"
    }

    Step "preflight-disco" {
        $freeGB = [math]::Round((Get-PSDrive C).Free / 1GB, 1)
        if ($freeGB -lt 1) { throw "Solo ${freeGB}GB libres en C:" }
        "${freeGB}GB libres"
    }

    Step "preflight-destino" {
        if (-not (Test-Path -LiteralPath (Join-Path $Target ".git"))) { throw "El destino no es un clon git" }
        $st = Git @("-C", $Target, "status", "--short")
        if ($st) { throw "El destino tiene cambios locales: $($st -replace "`r?`n", "; ")" }
        "destino limpio"
    }

    Step "preflight-apache" {
        $svc = Get-Service -Name $ApacheService -ErrorAction SilentlyContinue
        if ($null -eq $svc) {
            Warn-Step "Servicio Apache '$ApacheService' no encontrado. El reinicio se hara con httpd.exe -k restart."
            "sin servicio"
        } else {
            "$($svc.Name) $($svc.Status)"
        }
    }

    if ($DryRun) {
        Write-Output "DRYRUN|preflight ok; acciones pendientes: build, git-sync, sync, clean-target, composer, down, backup, migrate, optimize, queue, storage:link, up, restart-apache, verify-commit, smoke"
        Write-Output ("SUMMARY|ok|{0}|{1}" -f $script:failed, $script:totalMs)
        exit 0
    }

    if (-not $SkipBuild) {
        Step "build-frontend" {
            Push-Location $Source
            try {
                npm ci 2>&1 | Out-Null
                if ($LASTEXITCODE -ne 0) {
                    if (Test-Path -LiteralPath "node_modules") {
                        Warn-Step "npm ci fallo ($LASTEXITCODE); se reutiliza node_modules existente."
                    } else {
                        throw "npm ci fallo y no hay node_modules"
                    }
                }
                $build = npm run build 2>&1 | Out-String
                if ($LASTEXITCODE -ne 0) { throw "npm run build fallo: $build" }
                ($build -split "`r?`n" | Where-Object { $_ -match "built in|modules transformed" } | Select-Object -Last 1)
            } finally { Pop-Location }
        }
    } else {
        Write-Output "STEP|build-frontend|SKIP|0ms|--skip-build"
    }

    if (-not $SkipGitSync) {
        Step "git-sync" {
            Git @("-C", $Target, "fetch", "--no-tags", $Source, "main") | Out-Null
            Git @("-C", $Target, "merge", "--ff-only", "FETCH_HEAD") | Out-Null
            (Git @("-C", $Target, "rev-parse", "HEAD")).Trim()
        }
    } else {
        Write-Output "STEP|git-sync|SKIP|0ms|--skip-git-sync"
    }

    $hasComposer = $null -ne (Get-Command composer -ErrorAction SilentlyContinue)
    Step "sync-archivos" {
        $dirExcludes = @(".git", "node_modules", "tests", ".opencode", "storage\app\backups", "storage\app\public", "storage\framework", "storage\logs")
        if ($hasComposer) { $dirExcludes += @("vendor") }
        $fileExcludes = @(".env*", "*.log", "*.md", "phpunit.xml", "vite.config.js", "tsconfig.json", ".phpunit.result.cache", "public\hot")
        $args = @($Source, $Target, "/MIR", "/NJH", "/NJS", "/NP", "/XD") + $dirExcludes + @("/XF") + $fileExcludes
        & robocopy @args | Out-Null
        if ($LASTEXITCODE -gt 7) { throw "robocopy exit=$LASTEXITCODE" }
        "mirror ok (storage de destino preservado)"
    }

    Step "clean-target" {
        $hot = Join-Path $Target "public\hot"
        if (Test-Path -LiteralPath $hot) {
            Remove-Item -LiteralPath $hot -Force
            "hot obsoleto eliminado"
        } else {
            "sin hot obsoleto"
        }
    }

    if ($hasComposer) {
        Step "composer-install" {
            Push-Location $Target
            try {
                $attempt = 0
                do {
                    $attempt += 1
                    composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | Select-Object -Last 2 | Out-String
                } while ($LASTEXITCODE -ne 0 -and $attempt -lt 3)
                if ($LASTEXITCODE -ne 0) { throw "composer fallo tras $attempt intentos" }
                "vendor ok (intento $attempt)"
            } finally { Pop-Location }
        }
    } else {
        Write-Output "STEP|composer-install|SKIP|0ms|sin composer: vendor sincronizado"
    }

    Step "composer-audit" {
        Push-Location $Target
        try {
            $out = composer audit --no-dev --format=summary --no-interaction 2>&1 | Out-String
            if ($LASTEXITCODE -ne 0) {
                Warn-Step "composer audit reporta avisos: $(($out -split "`r?`n" | Where-Object { $_ -match "advisor" } | Select-Object -Last 1))"
                "avisos"
            } else {
                "sin avisos"
            }
        } finally { Pop-Location }
    }

    if (-not $NoMaintenance) {
        Step "maintenance-down" { & $wampPhp (Join-Path $Target "artisan") down | Out-String }
    }

    Step "backup-bd" {
        $out = & $wampPhp (Join-Path $Target "artisan") app:backup-database | Out-String
        if ($LASTEXITCODE -ne 0) { throw $out }
        ($out.Trim() -split "`n" | Select-Object -Last 1)
    }

    Step "migrate" {
        $out = & $wampPhp (Join-Path $Target "artisan") migrate --force | Out-String
        if ($LASTEXITCODE -ne 0) { throw $out }
        ($out.Trim() -split "`n" | Select-Object -Last 1)
    }

    Step "migrate-status" {
        $out = & $wampPhp (Join-Path $Target "artisan") migrate:status | Out-String
        $pending = ($out -split "`r?`n" | Where-Object { $_ -match "Pending" }).Count
        if ($pending -gt 0) { throw "$pending migracion(es) pendiente(s) tras migrate --force" }
        "sin pendientes"
    }

    Step "caches" {
        & $wampPhp (Join-Path $Target "artisan") optimize | Out-Null
        if ($LASTEXITCODE -ne 0) { throw "optimize fallo" }
        "config+events+route+view"
    }

    Step "queue-restart" {
        & $wampPhp (Join-Path $Target "artisan") queue:restart | Out-Null
        if ($LASTEXITCODE -ne 0) { throw "queue:restart fallo" }
        "workers reiniciados"
    }

    Step "storage-link" {
        $link = Join-Path $Target "public\storage"
        $real = Join-Path $Target "storage\app\public"
        if (Test-Path -LiteralPath $link) {
            $item = Get-Item -LiteralPath $link -Force
            if (-not ($item.Attributes -band [IO.FileAttributes]::ReparsePoint)) {
                if (-not (Test-Path -LiteralPath $real)) { New-Item -ItemType Directory -Force -Path $real | Out-Null }
                & robocopy $link $real /E /MOV /NFL /NDL /NJH /NJS /NP | Out-Null
                if ($LASTEXITCODE -gt 7) { throw "no se pudo migrar el contenido de public/storage" }
                Remove-Item -LiteralPath $link -Recurse -Force
                "public/storage era un directorio real: migrado a storage/app/public"
            }
        }
        & $wampPhp (Join-Path $Target "artisan") storage:link | Out-Null
        if ($LASTEXITCODE -ne 0) { throw "storage:link fallo" }
        if (-not (Test-Path -LiteralPath $link)) { throw "storage:link no creo public/storage" }
        $after = Get-Item -LiteralPath $link -Force
        if (-not ($after.Attributes -band [IO.FileAttributes]::ReparsePoint)) { throw "public/storage sigue sin ser un enlace" }
        "enlace verificado"
    }

    if (-not $NoMaintenance) {
        Step "maintenance-up" { & $wampPhp (Join-Path $Target "artisan") up | Out-String }
    }

    Step "restart-apache" {
        $svc = Get-Service -Name $ApacheService -ErrorAction SilentlyContinue
        if ($null -ne $svc) {
            try {
                Restart-Service -Name $ApacheService -Force -ErrorAction Stop
                Start-Sleep -Seconds 3
                "servicio $ApacheService reiniciado"
            } catch {
                Warn-Step "No se pudo reiniciar el servicio ($($_.Exception.Message)); se intenta con httpd.exe"
                $httpd = Get-ChildItem "C:\wamp64\bin\apache\apache2.4.*\bin\httpd.exe" -ErrorAction SilentlyContinue | Select-Object -First 1
                if ($null -eq $httpd) { throw "Apache no se pudo reiniciar y no se encontro httpd.exe" }
                & $httpd.FullName -k restart | Out-Null
                Start-Sleep -Seconds 3
                "httpd -k restart"
            }
        } else {
            $httpd = Get-ChildItem "C:\wamp64\bin\apache\apache2.4.*\bin\httpd.exe" -ErrorAction SilentlyContinue | Select-Object -First 1
            if ($null -eq $httpd) { throw "No hay servicio Apache ni httpd.exe" }
            & $httpd.FullName -k restart | Out-Null
            Start-Sleep -Seconds 3
            "httpd -k restart (sin servicio)"
        }
    }

    Step "verify-commit" {
        $sourceHead = (Git @("-C", $Source, "rev-parse", "HEAD")).Trim()
        $targetHead = (Git @("-C", $Target, "rev-parse", "HEAD")).Trim()
        if ($sourceHead -ne $targetHead) { throw "commit destino ($targetHead) != origen ($sourceHead)" }
        $targetHead.Substring(0, 7)
    }

    Step "smoke" {
        foreach ($path in @("/up", "/login")) {
            $ok = $false
            for ($i = 1; $i -le 3; $i++) {
                try {
                    $r = Invoke-WebRequest -Uri ($BaseUrl.TrimEnd("/") + $path) -UseBasicParsing -TimeoutSec 30
                    if ($r.StatusCode -eq 200) { $ok = $true; break }
                } catch {
                    Start-Sleep -Seconds 2
                }
            }
            if (-not $ok) { throw "$path no respondio 200" }
        }
        "/up y /login 200"
    }

    Write-Output ("SUMMARY|done|{0}|{1}" -f $script:failed, $script:totalMs)
    if ($script:warnings.Count -gt 0) {
        Write-Output ("SUMMARY|warnings|{0}|{1}" -f $script:warnings.Count, ($script:warnings -join " | "))
    }
    exit $script:failed
} catch {
    Write-Output ("SUMMARY|aborted|{0}|{1}" -f $script:failed, $script:totalMs)
    exit 1
}
