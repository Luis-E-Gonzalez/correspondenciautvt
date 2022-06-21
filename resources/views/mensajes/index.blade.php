@extends('layout.layout')

@section('content')
@section('header')
<script src='{{asset('src/js/zinggrid.min.js')}}'></script>
<script src='{{asset('src/js/zinggrid-es.js')}}'></script>
<script>
if (es) ZingGrid.registerLanguage(es, 'custom');
</script>
@endsection
{{-- Inicia Reporte --}}
<div class="card">
    <div class="card-header">
        @if (Session::has('mensaje'))
            <div class="alert alert-success">
                <strong>{{ Session::get('mensaje') }}</strong>
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            </div>
        @endif
        <div class="row">
            <div class="col-sm-11">
                <h2 align="center">Usuarios</h2>
                <a href="{{url('mensajes/create')}}"><button class="btn btn-success"><i class="fas fa-user-alt"></i></button></a>
            </div>
        </div>
    </div>
</div>

 <div class="card-body">
    <zing-grid
        lang="custom"
        caption='Reporte de mensajes'
        sort
        search
        pager
        page-size='10'
        page-size-options='10,15,20,25,30'
        layout='row'
        viewport-stop
        theme='android'
        id='zing-grid'
        filter
        selector
        data="{{ $mensajes }}">
            <zg-column index='mensaje' header='Mensaje'  type='text'></zg-column>
            <zg-column index='asunto' header='Actividad'  type='text'></zg-column>
            <zg-column index='comunicado' header='Comunicado'  type='text'></zg-column>
            <zg-column index='descripcion' header='Descripcion de la actividad'  type='text'></zg-column>
            <zg-column index='titulo' header='Título'  type='text'></zg-column>
            <zg-column index='nombre' header='Nombre'  type='text'></zg-column>
            <zg-column index='app' header='Apellido Paterno'  type='text'></zg-column>
            <zg-column index='apm' header='Apellido Materno'  type='text'></zg-column>
            {{-- Falta que aparezcan los botones de editar y eliminar --}}
            <zg-column align="center" filter ="disabled" index='operaciones' header='Operaciones' type='text'></zg-column>
        </zg-colgroup>
    </zing-grid>
</div>
{{-- Termina Reporte --}}
@endsection
