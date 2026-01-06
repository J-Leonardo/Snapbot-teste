import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router, ActivatedRoute, RouterModule } from '@angular/router';
import { MatSnackBar } from '@angular/material/snack-bar';
import { MATERIAL_MODULES } from '../../shared/material';
import { LayoutComponent } from '../../shared/layout/layout';
import { LoadingComponent } from '../../shared/loading/loading';
import { DeviceService } from '../../core/services/device';
import { Device } from '../../core/models/device.model';

@Component({
  selector: 'app-device-form',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterModule,
    ...MATERIAL_MODULES,
    LayoutComponent,
    LoadingComponent
  ],
  templateUrl: './device-form.html',
  styleUrl: './device-form.scss'
})
export class DeviceFormComponent implements OnInit {
  deviceForm!: FormGroup;
  loading = false;
  isEditMode = false;
  deviceId: number | null = null;
  maxDate = new Date(); // Data máxima é hoje

  constructor(
    private fb: FormBuilder,
    private deviceService: DeviceService,
    private router: Router,
    private route: ActivatedRoute,
    private snackBar: MatSnackBar
  ) {}

  ngOnInit(): void {
    this.initForm();
    this.checkEditMode();
  }

  initForm(): void {
    this.deviceForm = this.fb.group({
      name: ['', [Validators.required, Validators.maxLength(255)]],
      location: ['', [Validators.required, Validators.maxLength(255)]],
      purchase_date: ['', [Validators.required, this.dateNotFutureValidator]],
      in_use: [false]
    });
  }

  // Validador customizado para impedir datas futuras
  dateNotFutureValidator(control: any) {
    if (!control.value) {
      return null;
    }

    const selectedDate = new Date(control.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    selectedDate.setHours(0, 0, 0, 0);

    return selectedDate > today ? { futureDate: true } : null;
  }

  checkEditMode(): void {
    const id = this.route.snapshot.paramMap.get('id');
    
    if (id) {
      this.isEditMode = true;
      this.deviceId = Number(id);
      this.loadDevice();
    }
  }

  loadDevice(): void {
    if (!this.deviceId) return;

    this.loading = true;

    // Buscar dispositivo específico
    this.deviceService.getDevices({ page: 1 }).subscribe({
      next: (response) => {
        const device = response.data.find(d => d.id === this.deviceId);
        
        if (device) {
          this.deviceForm.patchValue({
            name: device.name,
            location: device.location,
            purchase_date: device.purchase_date,
            in_use: device.in_use
          });
        } else {
          this.snackBar.open('Dispositivo não encontrado', 'Fechar', {
            duration: 3000,
            panelClass: ['error-snackbar']
          });
          this.router.navigate(['/devices']);
        }
        
        this.loading = false;
      },
      error: () => {
        this.loading = false;
        this.snackBar.open('Erro ao carregar dispositivo', 'Fechar', {
          duration: 3000,
          panelClass: ['error-snackbar']
        });
        this.router.navigate(['/devices']);
      }
    });
  }

  onSubmit(): void {
    if (this.deviceForm.invalid) {
      this.deviceForm.markAllAsTouched();
      return;
    }

    this.loading = true;

    const deviceData = {
      ...this.deviceForm.value,
      purchase_date: this.formatDate(this.deviceForm.value.purchase_date)
    };

    const request = this.isEditMode && this.deviceId
      ? this.deviceService.updateDevice(this.deviceId, deviceData)
      : this.deviceService.createDevice(deviceData);

    request.subscribe({
      next: (response) => {
        this.snackBar.open(
          response.message || `Dispositivo ${this.isEditMode ? 'atualizado' : 'criado'} com sucesso!`,
          'Fechar',
          { duration: 3000 }
        );
        this.router.navigate(['/devices']);
      },
      error: (error) => {
        this.loading = false;
        const message = error.error?.message || 'Erro ao salvar dispositivo';
        this.snackBar.open(message, 'Fechar', {
          duration: 5000,
          panelClass: ['error-snackbar']
        });
      }
    });
  }

  formatDate(date: any): string {
    if (!date) return '';
    
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    
    return `${year}-${month}-${day}`;
  }

  cancel(): void {
    this.router.navigate(['/devices']);
  }

  getErrorMessage(field: string): string {
    const control = this.deviceForm.get(field);
    
    if (control?.hasError('required')) {
      return 'Campo obrigatório';
    }
    
    if (control?.hasError('maxlength')) {
      return 'Máximo de 255 caracteres';
    }
    
    if (control?.hasError('futureDate')) {
      return 'A data de compra não pode ser futura';
    }
    
    return '';
  }
}