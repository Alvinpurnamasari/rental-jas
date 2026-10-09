
@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="mb-4">
            <h1 class="h3 mb-1">Laporan Barang Hilang</h1>
            <p class="text-muted mb-0">
                Laporan barang rental yang dinyatakan hilang saat pengembalian.
            </p>
        </div>

        @include('reports.partials.lost-items-content')
    </div>
@endsection