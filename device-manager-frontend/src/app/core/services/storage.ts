import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class StorageService {
  
  setItem(key: string, value: any): void {
    localStorage.setItem(key, JSON.stringify(value));
  }

  getItem<T>(key: string): T | null {
    const item = localStorage.getItem(key);
    return item ? JSON.parse(item) : null;
  }

  removeItem(key: string): void {
    localStorage.removeItem(key);
  }

  clear(): void {
    localStorage.clear();
  }

  // Métodos específicos para token
  setToken(token: string): void {
    this.setItem('auth_token', token);
  }

  getToken(): string | null {
    return this.getItem<string>('auth_token');
  }

  removeToken(): void {
    this.removeItem('auth_token');
  }

  // Métodos para filtros
  setFilters(filters: any): void {
    this.setItem('device_filters', filters);
  }

  getFilters(): any {
    return this.getItem('device_filters');
  }

  clearFilters(): void {
    this.removeItem('device_filters');
  }
}