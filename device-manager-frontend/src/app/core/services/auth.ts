import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { Router } from '@angular/router';
import { environment } from '../../../environments/environment';
import { 
  User, 
  AuthResponse, 
  LoginRequest, 
  RegisterRequest 
} from '../models/user.model';
import { StorageService } from './storage';

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private apiUrl = environment.apiUrl;
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  public currentUser$ = this.currentUserSubject.asObservable();

  constructor(
    private http: HttpClient,
    private router: Router,
    private storage: StorageService
  ) {
    // Verificar se há token ao iniciar
    if (this.storage.getToken()) {
      this.loadCurrentUser();
    }
  }

  register(data: RegisterRequest): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/register`, data).pipe(
      tap(response => this.handleAuthResponse(response))
    );
  }

  login(data: LoginRequest): Observable<AuthResponse> {
    return this.http.post<AuthResponse>(`${this.apiUrl}/login`, data).pipe(
      tap(response => this.handleAuthResponse(response))
    );
  }

  logout(): Observable<any> {
    return this.http.post(`${this.apiUrl}/logout`, {}).pipe(
      tap(() => {
        this.storage.removeToken();
        this.storage.clear();
        this.currentUserSubject.next(null);
        this.router.navigate(['/auth/login']);
      })
    );
  }

  loadCurrentUser(): void {
    this.http.get<{ user: User }>(`${this.apiUrl}/me`).subscribe({
      next: (response) => {
        this.currentUserSubject.next(response.user);
      },
      error: () => {
        this.storage.removeToken();
        this.currentUserSubject.next(null);
      }
    });
  }

  private handleAuthResponse(response: AuthResponse): void {
    this.storage.setToken(response.token);
    this.currentUserSubject.next(response.user);
  }

  isAuthenticated(): boolean {
    return !!this.storage.getToken();
  }

  getCurrentUser(): User | null {
    return this.currentUserSubject.value;
  }
}