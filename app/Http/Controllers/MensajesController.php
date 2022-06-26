<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\UpdateMensajesRequest;
use App\Models\Mensajes;
use App\Models\User;
use App\Models\Actividades;
use Illuminate\Support\Facades\Mail;
use App\Mail\mensaje_error;
use DB;

class MensajesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       //Consulta para obtener los mensajes de una actividad cambiando el idu_users por el nombre del usuario app y apm y el idac_actividades por el nombre de la actividad
        $mensajes = DB::table('mensajes')
            ->join('users', 'users.idu', '=', 'mensajes.idu_users')
            ->join('actividades', 'actividades.idac', '=', 'mensajes.idac_actividades')
            ->select('mensajes.idm', 'users.titulo','users.nombre', 'users.app', 'users.apm','actividades.asunto', 'actividades.comunicado','actividades.descripcion', 'mensajes.mensaje', 'mensajes.fecha')
            ->orderby('fecha', 'DESC')
            ->get();
        //Recorre los datos de la consulta y los guarda en una variable
        $array=array();

        function btn ($idm) {
            $btn = '<a href="/mensaje/{{$idm}}/edit" class="btn btn-primary btn-sm">Editar</a>'; //Crea el boton de editar
            $btn .= '<form method="POST" action="/mensaje/{{$idm}}/delete" accept-charset="UTF-8" style="display:inline">
                        '.method_field('DELETE').'
                        '.csrf_field().'
                        <button class="btn btn-danger btn-sm" type="button" data-toggle="modal" data-target="#confirmDelete" data-title="Eliminar Mensaje" data-message="¿Esta seguro de eliminar este mensaje?">Eliminar</button>
                    </form>';
            return $btn;
        }

        foreach($mensajes as $mensaje){
            $array[]=array(
                'idm'=>$mensaje->idm,
                'titulo'=>$mensaje->titulo,
                'nombre'=>$mensaje->nombre,
                'app'=>$mensaje->app,
                'apm'=>$mensaje->apm,
                'asunto'=>$mensaje->asunto,
                'comunicado'=>$mensaje->comunicado,
                'descripcion'=>$mensaje->descripcion,
                'mensaje'=>$mensaje->mensaje,
                'fecha'=>$mensaje->fecha,
                'operaciones'=>btn($mensaje->idm)
            );
        }
        //Retorna la vista con los datos de la consulta

        return view('mensajes.index', compact('mensajes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreMensajesRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //FUNCION QUE GUARDA LOS DATOS DE UN NUEVO MENSAJE
        $mensaje = new Mensajes;
        $mensaje->idu_users = $request->idu_users;
        $mensaje->idac_actividades = $request->idac_actividades;
        $mensaje->mensaje = $request->mensaje;
        $mensaje->fecha = $request->fecha;
        $mensaje->save();

        //consulta para obtener el correo del usuario que realiza el mensaje
        $user = User::find($request->idu_users);
        $correo = $user->email;

       //obtiene el contenido del mensaje y lo guarda en una variable
        $contenido = $request->mensaje;




        //Envia el correo al usuario con el mensaje
        Mail::to($correo)->send(new mensaje_error($contenido, $user));

        //Retorna a la vista de los mensajes
        return redirect('/panel');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Mensajes  $mensajes
     * @return \Illuminate\Http\Response
     */
    public function show(Mensajes $mensajes)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Mensajes  $mensajes
     * @return \Illuminate\Http\Response
     */
    public function edit(Mensajes $mensajes)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateMensajesRequest  $request
     * @param  \App\Models\Mensajes  $mensajes
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateMensajesRequest $request, Mensajes $mensajes)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Mensajes  $mensajes
     * @return \Illuminate\Http\Response
     */
    public function destroy(Mensajes $mensajes)
    {
        //
    }
}
