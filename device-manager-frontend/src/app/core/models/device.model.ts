export interface Device {
  id: number;
  name: string;
  location: string;
  purchase_date: string;
  in_use: boolean;
  user_id: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
}

export interface DeviceResponse {
  data: Device[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export interface DeviceFilters {
  page?: number;
  in_use?: boolean;
  location?: string;
  purchase_date_start?: string;
  purchase_date_end?: string;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}