@extends('layout.auth')

@section('contenido')

<!-- Vista donde se cambia la contraseña -->

<div align="center">

    <h3>Cambiar contraseña</h3>

</div>

<form method="POST" action="{{ route('password.update') }}">

    @csrf

    <div class="form-group">

        <label for='password'>

            Nueva Contraseña:

        </label>

		<input  type="password" 
		        class="form-control rounded-left"  
		        class="@error('password') is-invalid @enderror" 
		        placeholder="Contraseña" 
		        name="password"
		>

        @error('password')

            <div    class="form-text text-danger" 
                    role"alert"
            >

                {{ $message }}

            </div>

        @enderror

	</div>

    <div class="form-group ">

        <label for='password_confirmation'>

            Confirma tu Contraseña:

        </label>

        <input  id="password" 
                type="password" 
                class="form-control @error('password') is-invalid @enderror" 
                name="password_confirmation"
                placeholder="Confirmar contraseña"
        >

    </div>

    <div class="form-group">

        <button type="submit" 
                class="btn btn-primary btn-sm rounded submit"
        >

            Recuperar contraseña

        </button>

    </div>
    <!-- El campo email oculto de el formulario -->
     <div class="form-group" style="visibility:hidden"> 

        <label for='email'> 

            Correo Electrónico

        </label>

        <input  type="hidden" 
                name="token"  
                value="{{ $token }}"
                
        >

        <input  type="text" 
                class="form-control rounded-left"  
                class="@error('email') is-invalid @enderror" 
                placeholder="Correo electrónico" 
                name="email" 
                value="{{$email}}" 
                
        >

        @error('email')

            <div class="form-text text-danger">

                {{ $message }}

            </div>

        @enderror

	</div>

    
</form>

@endsection
