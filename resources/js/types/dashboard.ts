export interface MonthlyStat {
  month: number;
  ci: number;
  of: number;
  total: number;
}

export interface DashboardProps {
  stats: MonthlyStat[];
  selectedYear: number;
  availableYears: number[];
  isAdmin: boolean;
}