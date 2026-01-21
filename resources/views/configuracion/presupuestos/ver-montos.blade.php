@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-clock-history"></i> Historial de Cambios - Montos de Configuración
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Código</th>
                            <th class="text-end">Monto Actual</th>
                            <th>Última Modificación</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($montos as $monto)
                        <tr>
                            <td>{{ $monto->id }}</td>
                            <td><strong>{{ $monto->nombre }}</strong></td>
                            <td><code>{{ $monto->codigo }}</code></td>
                            <td class="text-end">
                                <strong class="text-primary">$ {{ number_format($monto->monto, 0, ',', '.') }}</strong>
                            </td>
                            <td>
                                @if($monto->updated_at)
                                    <span class="badge bg-info">
                                        {{ $monto->updated_at->format('d/m/Y H:i:s') }}
                                    </span>
                                    @if($monto->updated_at->diffInMinutes() < 5)
                                        <small class="text-success ms-2">(Hace {{ $monto->updated_at->diffForHumans() }})</small>
                                    @endif
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                <a href="{{ route('configuracion.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Configuración
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

