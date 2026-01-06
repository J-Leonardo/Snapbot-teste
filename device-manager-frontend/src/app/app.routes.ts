import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth-guard';

export const routes: Routes = [
  {
    path: '',
    redirectTo: '/devices',
    pathMatch: 'full'
  },
  {
    path: 'auth/login',
    loadComponent: () => import('./auth/login/login').then(m => m.LoginComponent)
  },
  {
    path: 'auth/register',
    loadComponent: () => import('./auth/register/register').then(m => m.RegisterComponent)
  },
  {
    path: 'devices',
    canActivate: [authGuard],
    children: [
      {
        path: '',
        loadComponent: () => import('./devices/device-list/device-list').then(m => m.DeviceListComponent)
      },
      {
        path: 'new',
        loadComponent: () => import('./devices/device-form/device-form').then(m => m.DeviceFormComponent)
      },
      {
        path: 'edit/:id',
        loadComponent: () => import('./devices/device-form/device-form').then(m => m.DeviceFormComponent)
      }
    ]
  },
  {
    path: '**',
    redirectTo: '/auth/login'
  }
];