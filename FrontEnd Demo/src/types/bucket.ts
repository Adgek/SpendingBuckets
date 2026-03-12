export interface Bucket {
  id: string;
  name: string;
  priority: number;
  current: number;
  target: number;
  category?: string;
}

export type TabType = 'deposit' | 'expense' | 'transfer';