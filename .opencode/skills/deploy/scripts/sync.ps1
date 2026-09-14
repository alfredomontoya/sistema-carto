<#
.SYNOPSIS
  Despliegue de Carto al directorio WAMP con reporte por paso.
.DESCRIPTION
  Preflight, respaldo, build, sync con robocopy, migraciones, caches,
  storage:link y smoke test. Emite una linea por paso:
    STEP|nombre|OK|1234ms|detalle
  y termina con SUMMARY|ok|fallidos|ms_total. En -DryRun solo preflight.
.EXAMPLE
  .\sync.ps1 -DryRun
  .\sync.ps1 -Target "C:\wamp64\www\carto" -BaseUrl "http://localhost"
#>
[CmdletBinding()]
param(
    [string]$Source = "D:\progra\carto",
    [string]$Target = "C:\wamp64\www\carto",
    [string]$BaseUrl = "http://localhost",
    [switch]$DryRun,
    [switch]$SkipBuild,
    [switch]$NoMaintenance
)

$ErrorActionPreference = "Stop"
$script:failed = 0
$script:totalMs = 0

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
    if ($null -eq $line) { return $null }
    return ($line -replace ("^\s*" + $key + "\s*=\s*"), "").Trim().Trim('"').Trim("'")
}

try {
    Step "preflight-git" {
        $st = git -C $Source status --short
        if ($st) { throw "Hay cambios sin commitear en origen" }
        "limpio"
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

    if ($DryRun) {
        Write-Output "DRYRUN|preflight ok; acciones pendientes: backup, build, sync, migrate, caches, storage:link, smoke"
        Write-Output ("SUMMARY|ok|{0}|{1}" -f $script:failed, $script:totalMs)
        exit 0
    }

    if (-not $NoMaintenance) {
        Step "maintenance-down" { & $wampPhp (Join-Path $Target "artisan") down | Out-String }
    }

    Step "backup-bd" {
        $out = & $wampPhp (Join-Path $Target "artisan") app:backup-database | Out-String
        if ($LASTEXITCODE -ne 0) { throw $out }
        ($out.Trim() -split "`n" | Select-Object -Last 1)
    }

    if (-not $SkipBuild) {
        Step "build-frontend" {
            Push-Location $Source
            try {
                npm ci 2>&1 | Out-Null
                if ($LASTEXITCODE -ne 0) { throw "npm ci fallo" }
                npm run build 2>&1 | Select-Object -Last 2 | Out-String
            } finally { Pop-Location }
        }
    } else {
        Write-Output "STEP|build-frontend|SKIP|0ms|--skip-build"
    }

    $hasComposer = $null -ne (Get-Command composer -ErrorAction SilentlyContinue)
    Step "sync-archivos" {
        $excludes = @(".git", "node_modules", "tests", ".env*", "public\hot", ".opencode\node_modules")
        $logDir = Join-Path "storage" "logs"
        $args = @($Source, $Target, "/MIR", "/NJH", "/NJS", "/NP", "/XD") + $excludes + @("/XF", "*.log") + @("/XD", $logDir)
        if ($hasComposer) { $args += @("vendor") }
        & robocopy @args | Out-Null
        if ($LASTEXITCODE -gt 7) { throw "robocopy exit=$LASTEXITCODE" }
        "mirror ok"
    }

    if ($hasComposer) {
        Step "composer-install" {
            Push-Location $Target
            try {
                composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | Select-Object -Last 2 | Out-String
            } finally { Pop-Location }
        }
    } else {
        Write-Output "STEP|composer-install|SKIP|0ms|sin composer: vendor sincronizado"
    }

    Step "migrate" {
        $out = & $wampPhp (Join-Path $Target "artisan") migrate --force | Out-String
        if ($LASTEXITCODE -ne 0) { throw $out }
        ($out.Trim() -split "`n" | Select-Object -Last 1)
    }

    Step "caches" {
        foreach ($c in @("config:cache", "route:cache", "view:cache")) {
            & $wampPhp (Join-Path $Target "artisan") $c | Out-Null
            if ($LASTEXITCODE -ne 0) { throw "$c fallo" }
        }
        "config+route+view"
    }

    Step "storage-link" {
        $out = & $wampPhp (Join-Path $Target "artisan") storage:link 2>&1 | Out-String
        ($out.Trim() -split "`n" | Select-Object -Last 1)
    }

    Step "smoke" {
        foreach ($path in @("/up", "/login")) {
            $r = Invoke-WebRequest -Uri ($BaseUrl.TrimEnd("/") + $path) -UseBasicParsing -TimeoutSec 30
            if ($r.StatusCode -ne 200) { throw "$path -> $($r.StatusCode)" }
        }
        "/up y /login 200"
    }

    if (-not $NoMaintenance) {
        Step "maintenance-up" { & $wampPhp (Join-Path $Target "artisan") up | Out-String }
    }

    Write-Output ("SUMMARY|{0}|{1}|{2}" -f "done", $script:failed, $script:totalMs)
    exit $script:failed
} catch {
    Write-Output ("SUMMARY|aborted|{0}|{1}" -f $script:failed, $script:totalMs)
    exit 1
}
