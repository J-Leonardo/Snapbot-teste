import { Injectable } from '@angular/core';
import { HttpClient, HttpParams } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Device, DeviceResponse, DeviceFilters } from '../models/device.model';

@Injectable({
  providedIn: 'root'
})
export class DeviceService {
  private apiUrl = `${environment.apiUrl}/devices`;

  constructor(private http: HttpClient) {}

  getDevices(filters: DeviceFilters = {}): Observable<DeviceResponse> {
    let params = new HttpParams();

    Object.keys(filters).forEach(key => {
      const value = (filters as any)[key];
      if (value !== null && value !== undefined && value !== '') {
        params = params.set(key, value.toString());
      }
    });

    return this.http.get<DeviceResponse>(this.apiUrl, { params });
  }

  createDevice(device: Partial<Device>): Observable<{ message: string; data: Device }> {
    return this.http.post<{ message: string; data: Device }>(this.apiUrl, device);
  }

  updateDevice(id: number, device: Partial<Device>): Observable<{ message: string; data: Device }> {
    return this.http.put<{ message: string; data: Device }>(`${this.apiUrl}/${id}`, device);
  }

  deleteDevice(id: number): Observable<{ message: string }> {
    return this.http.delete<{ message: string }>(`${this.apiUrl}/${id}`);
  }

  toggleUse(id: number): Observable<{ message: string; data: Device }> {
    return this.http.patch<{ message: string; data: Device }>(`${this.apiUrl}/${id}/use`, {});
  }
}