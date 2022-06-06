@extends('layout.auth')
@section('contenido')
    <form method="POST" action="{{ route('login') }}">
        @csrf
        <!-- Esta es la vista principal de Login los demas estilos estan en el Carpeta layout/auth/auth.blade.php -->
        INICIAR SESIÓN
        <br>
        <br>
        <div class="form-group">
            <input type="text" class="form-control rounded-left" class="@error('email') is-invalid @enderror"
                placeholder="Correo electrónico" name="email">
            @error('email')
                <div class="form-text text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            {{-- <input type="password" class="form-control rounded-left"  class="@error('password') is-invalid @enderror" placeholder="Contraseña" name="password" > --}}
            <div class="input-group">
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password"
                    name="password" value="{{ old('password') }}">
                <button class="btn btn-outline" type="button" id="show_password">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            @error('password')
                <div class="form-text text-danger">{{ $message }}</div>
            @enderror
        </div>


        <div class="form-group d-md-flex">
            <div align="center">
                <a href="{{ route('password.request') }}">{{ '¿Olvidaste tu contraseña?' }}</a>
                <button type="submit" class="btn btn-primary btn-sm rounded submit mt-3">Iniciar sesión</button>
            </div>
        </div>
        <br>
        <br>
        <div class="card-footer">
            <div>
                <h6 align=center>Universidad Tecnol&oacute;gica del Valle de Toluca</h6>
                <h6 align=center>¡¡Siempre Cuervos!!</h6>
            </div>
        </div>
    </form>

    <script>
        //Script del boton de mostrar contraseña en el formulario de registro de unosolo item
        $('#show_password').on('click', function() {
            var password = $('#password');
            if (password.attr('type') === 'password') {
                password.attr('type', 'text');
            } else {
                password.attr('type', 'password');
            }
        });
    </script>

@endsection
