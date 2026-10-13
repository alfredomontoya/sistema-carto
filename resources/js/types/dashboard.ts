export interface MonthlyStat {
  month: number;
  total: number;
  ci: number;
  of: number;
  [typeCode: string]: number;
}

export interface GroupedStat {
  destino: string;
  total: number;
  ci: number;
  of: number;
  [typeCode: string]: number | string;
}

export interface DashboardProps {
  stats: MonthlyStat[];
  selectedYear: number;
  availableYears: number[];
  isAdmin: boolean;
}