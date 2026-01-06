import { Component, OnInit, ViewChild } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { FormBuilder, FormGroup, ReactiveFormsModule } from '@angular/forms';
import { MatPaginator } from '@angular/material/paginator';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MatDialog } from '@angular/material/dialog';
import { MATERIAL_MODULES } from '../../shared/material';
import { LayoutComponent } from '../../shared/layout/layout';
import { LoadingComponent } from '../../shared/loading/loading';
import { DeviceService } from '../../core/services/device';
import { StorageService } from '../../core/services/storage';
import { Device, DeviceFilters } from '../../core/models/device.model';

@Component({
  selector: 'app-device-list',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterModule,
    ...MATERIAL_MODULES,
    LayoutComponent,
    LoadingComponent
  ],
  templateUrl: './device-list.html',
  styleUrl: './device-list.scss'
})
export class DeviceListComponent implements OnInit {
  @ViewChild(MatPaginator) paginator!: MatPaginator;

  devices: Device[] = [];
  loading = false;
  displayedColumns: string[] = ['name', 'location', 'purchase_date', 'in_use', 'actions'];
  
  totalItems = 0;
  pageSize = 10;
  currentPage = 1;

  filterForm!: FormGroup;
  showFilters = false;

  constructor(
    private deviceService: DeviceService,
    private storageService: StorageService,
    private router: Router,
    private fb: FormBuilder,
    private snackBar: MatSnackBar,
    private dialog: MatDialog
  ) {}

  ngOnInit(): void {
    this.initFilterForm();
    this.loadSavedFilters();
    this.loadDevices();
  }

  initFilterForm(): void {
    this.filterForm = this.fb.group({
      location: [''],
      in_use: [''],
      purchase_date_start: [''],
      purchase_date_end: [''],
      sort_by: ['created_at'],
      sort_order: ['desc']
    });
  }

  loadSavedFilters(): void {
    const savedFilters = this.storageService.getFilters();
    if (savedFilters) {
      this.filterForm.patchValue(savedFilters);
      this.currentPage = savedFilters.page || 1;
    }
  }

  loadDevices(): void {
    this.loading = true;
    
    const filters: DeviceFilters = {
      ...this.filterForm.value,
      page: this.currentPage
    };

    // Salvar filtros no localStorage
    this.storageService.setFilters(filters);

    this.deviceService.getDevices(filters).subscribe({
      next: (response) => {
        this.devices = response.data;
        this.totalItems = response.meta.total;
        this.loading = false;
      },
      error: (error) => {
        this.loading = false;
        this.snackBar.open('Erro ao carregar dispositivos', 'Fechar', {
          duration: 3000,
          panelClass: ['error-snackbar']
        });
      }
    });
  }

  applyFilters(): void {
    this.currentPage = 1;
    this.loadDevices();
  }

  clearFilters(): void {
    this.filterForm.reset({
      location: '',
      in_use: '',
      purchase_date_start: '',
      purchase_date_end: '',
      sort_by: 'created_at',
      sort_order: 'desc'
    });
    this.storageService.clearFilters();
    this.currentPage = 1;
    this.loadDevices();
  }

  onPageChange(event: any): void {
    this.currentPage = event.pageIndex + 1;
    this.pageSize = event.pageSize;
    this.loadDevices();
  }

  toggleUse(device: Device): void {
    this.deviceService.toggleUse(device.id).subscribe({
      next: () => {
        this.snackBar.open('Status atualizado com sucesso', 'Fechar', {
          duration: 2000
        });
        this.loadDevices();
      },
      error: () => {
        this.snackBar.open('Erro ao atualizar status', 'Fechar', {
          duration: 3000,
          panelClass: ['error-snackbar']
        });
      }
    });
  }

  editDevice(device: Device): void {
    this.router.navigate(['/devices/edit', device.id]);
  }

  deleteDevice(device: Device): void {
    const dialogRef = this.dialog.open(ConfirmDialogComponent, {
      data: {
        title: 'Confirmar Exclusão',
        message: `Tem certeza que deseja excluir "${device.name}"?`
      }
    });

    dialogRef.afterClosed().subscribe(result => {
      if (result) {
        this.deviceService.deleteDevice(device.id).subscribe({
          next: () => {
            this.snackBar.open('Dispositivo excluído com sucesso', 'Fechar', {
              duration: 2000
            });
            this.loadDevices();
          },
          error: () => {
            this.snackBar.open('Erro ao excluir dispositivo', 'Fechar', {
              duration: 3000,
              panelClass: ['error-snackbar']
            });
          }
        });
      }
    });
  }

  newDevice(): void {
    this.router.navigate(['/devices/new']);
  }
}

// Componente de Diálogo de Confirmação
@Component({
  selector: 'app-confirm-dialog',
  standalone: true,
  imports: [...MATERIAL_MODULES],
  template: `
    <h2 mat-dialog-title>{{ data.title }}</h2>
    <mat-dialog-content>
      <p>{{ data.message }}</p>
    </mat-dialog-content>
    <mat-dialog-actions align="end">
      <button mat-button [mat-dialog-close]="false">Cancelar</button>
      <button mat-raised-button color="warn" [mat-dialog-close]="true">Confirmar</button>
    </mat-dialog-actions>
  `
})
export class ConfirmDialogComponent {
  constructor(@Inject(MAT_DIALOG_DATA) public data: any) {}
}

import { Inject } from '@angular/core';
import { MAT_DIALOG_DATA } from '@angular/material/dialog';