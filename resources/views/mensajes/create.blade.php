@extends('layout.layout')
@section('content')
    <div class="card">

        <div class="card-header bg-success text-light" style="text-align: center;">

            <h2> <a href="{{ route('reporte_actividades') }}" class="btn  btn-success btn-dual" type="button"><i
                        class="fas fa-arrow-left"></i> Regresar</a>
                &nbsp;&nbsp;
                Creación de mensajes</h2>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-xs-5 col-sm-5 col-md-5">
                    <form action="{{ route('mensajes.store') }}" method="POST" enctype="multipart/form-data"
                        id="form">
                        @csrf
                        <div class="row">
                            <div class="row">
                                <div class="col-xs-6 col-sm-6 col-md-6">
                                    <div class="row">
                                        <div class="col-xs-12 col-sm-12 col-md-12">
                                            <div class="form-group">
                                                <strong>Mensaje creado para:</strong>
                                                <input type="text" class="form-control" id="actividadcreador"
                                                    value="{{ $user[0]->titulo . ' ' . $user[0]->nombre . ' ' . $user[0]->app . ' ' . $user[0]->apm }}"
                                                    readonly>
                                                <input type="hidden" name="idusuario" value="{{ $user[0]->idu }}">
                                            </div>
                                        </div>
                                    </div>

                                </div>


                            </div>
                    </form>
                </div>


            </div>
        @endsection
