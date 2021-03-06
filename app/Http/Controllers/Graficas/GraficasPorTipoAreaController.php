<?php

namespace App\Http\Controllers\Graficas;

use App\Http\Controllers\Controller;
use App\Models\ResponsablesActividades;
use App\Models\SeguimientosActividades;
use App\Models\TiposActividades;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GraficasPorTipoAreaController extends Controller
{
    public function seguimiento($idac){
        return redirect()->route('Seguimiento',[ 'idac' => encrypt($idac) ]);
    }
    public function dashboard(User $user)
    {
        $tiposActividades = ResponsablesActividades::join('actividades','actividades.idac','responsables_actividades.idac_actividades')
            ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
            ->where('responsables_actividades.idu_users',$user->idu)
            ->where('actividades.aprobacion', 1)
            ->select('tipos_actividades.idtac', 'tipos_actividades.nombre')
            ->groupBy('tipos_actividades.nombre')
            ->get();
        return view('sistema.graficas.dashboard-por-tipo-area',[
            'tipo_actividades' => $tiposActividades,
            'user'=>$user->idu,
        ]);
    }

    public function getEstadisticasDeActividades(User $user, Request $request)
    {
      //Fecha de inicio y Fin
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($user,$inicio,$fin){
                $tipoActividad->actividadesCompletadas = $this->getActividadesCompletadas($user,$tipoActividad,$inicio,$fin)->count();
                $tipoActividad->actividadesEnProceso = $this->getActividadesEnProceso($user,$tipoActividad,$inicio,$fin)->count();
                $tipoActividad->actividadesSinEntregar = $this->getActividadesSinEntregar($user,$tipoActividad,$inicio,$fin)->count();
                $tipoActividad->actividadesConAcuseDeRecibido = $this->getActividadesConAcuseDeRecibido($user,$tipoActividad,$inicio,$fin)->count();
                $tipoActividad->actividadesSinAcuseDeRecibido = $this->getActividadesSinAcuseDeRecibido($user,$tipoActividad,$inicio,$fin)->count();

                $tipoActividad->actividadesCompletadasEnTiempo = $this->getActividadesCompletadasEnTiempo($user,$tipoActividad,$inicio,$fin)->count();
                $tipoActividad->actividadesCompletadasFueraDeTiempo = $this->getActividadesCompletadasFueraDeTiempo($user,$tipoActividad,$inicio,$fin)->count();

                $tipoActividad->actividadesEnProcesoEnTiempo = $this->getActividadesEnProcesoEnTiempo($user,$tipoActividad,$inicio,$fin)->count();
                $tipoActividad->actividadesEnProcesoFueraDeTiempo = $this->getActividadesEnProcesoFueraDeTiempo($user,$tipoActividad,$inicio,$fin)->count();

                $tipoActividad->actividadesTotales = $tipoActividad->actividadesCompletadas +
                                                    $tipoActividad->actividadesEnProceso +
                                                    $tipoActividad->actividadesSinEntregar;


                return $tipoActividad;
            });

        return $tiposActividades;
    }
    public function actividadesCompletadas(User $user, Request $request)
    {
      //Fecha de inicio y Fin
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($user,$inicio,$fin){
                $tipoActividad->actividades = $this->getActividadesCompletadas($user,$tipoActividad,$inicio,$fin);
                return $tipoActividad;
            });
        return $tiposActividades;
    }

    public function actividadesEnProceso(User $user, Request $request)
    {
      //Fecha de inicio y Fin
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($user,$inicio,$fin){
                $tipoActividad->actividades = $this->getActividadesEnProceso($user,$tipoActividad,$inicio,$fin);
                return $tipoActividad;
            });
        return $tiposActividades;
    }
    public function actividadesSinEntregar(User $user, Request $request)
    {
      //Fecha de inicio y Fin
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($user,$inicio,$fin){
                $tipoActividad->actividades = $this->getActividadesSinEntregar($user,$tipoActividad,$inicio,$fin);
                return $tipoActividad;
            });
        return $tiposActividades;
    }
    public function actividadesConAcuseDeRecibido(User $user, Request $request)
    {
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($user,$inicio,$fin){
                $tipoActividad->actividades = $this->getActividadesConAcuseDeRecibido($user,$tipoActividad,$inicio,$fin);
                return $tipoActividad;
            });
        return $tiposActividades;
    }
    public function actividadesSinAcuseDeRecibido(User $user, Request $request)
    {
      //Fecha de inicio y Fin
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($user,$inicio,$fin){
                $tipoActividad->actividades = $this->getActividadesSinAcuseDeRecibido($user,$tipoActividad,$inicio,$fin);
                return $tipoActividad;
            });
        return $tiposActividades;
    }

    public function actividadesCompletadasEnTiempo(User $user, Request $request){
      //Fecha de inicio y Fin
        $inicio = $request->inicio;
        $fin = $request->fin;
        return TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($user,$inicio,$fin){
                $tipoActividad->actividades = $this->getActividadesCompletadasEnTiempo($user,$tipoActividad,$inicio,$fin);
                return $tipoActividad;
            });
    }

    public function actividadesCompletadasFueraDeTiempo(User $user, Request $request){
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($user,$request){
                $tipoActividad->actividades = $this->getActividadesCompletadasFueraDeTiempo($user,$tipoActividad,$request->inicio,$request->fin);
                return $tipoActividad;
            });
        return $tiposActividades;
    }

    public function actividadesEnProcesoEnTiempo(User $user,  Request $request){
      //Fecha de inicio y Fin
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($user,$inicio,$fin){
                $tipoActividad->actividades = $this->getActividadesEnProcesoEnTiempo($user,$tipoActividad,$inicio,$fin);
                return $tipoActividad;
            });
        return $tiposActividades;
    }

    public function actividadesEnProcesoFueraDeTiempo(User $user, Request $request){
      //Fecha de inicio y Fin
        $inicio = $request->inicio;
        $fin = $request->fin;
        $tiposActividades = TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($user,$inicio,$fin){
                $tipoActividad->actividades = $this->getActividadesEnProcesoFueraDeTiempo($user,$tipoActividad,$inicio,$fin);
                return $tipoActividad;
            });
        return $tiposActividades;
    }


    public function getActividades(User $user, Request $request)
    {
        return  TiposActividades::whereIn('idtac', $request->tipos_actividades)
            ->get()
            ->each(function($tipoActividad) use($user,$request){
                $tipoActividad->actividades_completadas = $this->getActividadesCompletadas($user,$tipoActividad,$request->inicio,$request->fin);
                $tipoActividad->actividades_en_proceso = $this->getActividadesEnProceso($user,$tipoActividad,$request->inicio,$request->fin);
                $tipoActividad->actividades_sin_entregar = $this->getActividadesSinEntregar($user,$tipoActividad,$request->inicio,$request->fin);
                $tipoActividad->actividades_con_acuse_de_recibido = $this->getActividadesConAcuseDeRecibido($user,$tipoActividad,$request->inicio,$request->fin);
                $tipoActividad->actividades_sin_acuse_de_recibido = $this->getActividadesSinAcuseDeRecibido($user,$tipoActividad,$request->inicio,$request->fin);
                return $tipoActividad;
            });
    }

    public function getActividadesCompletadas(User $user, TiposActividades $tiposActividades, $inicio, $fin){
      //Fecha de inicio y Fin
        $inicio = new Carbon($inicio);
        $fin = new Carbon($fin);
        $actividades = ResponsablesActividades::join('seguimientos_actividades',
                'seguimientos_actividades.idreac_responsables_actividades',
                'responsables_actividades.idreac'
            )
            ->where('responsables_actividades.idu_users', $user->idu)
            ->where('seguimientos_actividades.porcentaje', 100)
            ->groupBy('responsables_actividades.idreac')
            ->select('responsables_actividades.idreac')
            ->get();
        if($actividades->count() < 1 ) return $actividades;
        return User::where('idu', $user->idu)
        ->join('responsables_actividades', 'idu_users', 'users.idu')
        ->join('actividades', 'idac', 'responsables_actividades.idac_actividades')
        ->join('areas','areas.idar','actividades.idar_areas')
        ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
        //->where('responsables_actividades.fecha','!=', null)
        ->where('actividades.idtac_tipos_actividades', $tiposActividades->idtac)
        ->where('actividades.fecha_inicio','>=', $inicio->format('Y-m-d'))
        ->where('actividades.fecha_fin','<=', $fin->format('Y-m-d'))
        ->whereIn('responsables_actividades.idreac', $actividades)
        ->where('actividades.aprobacion', 1)
        ->select(
            'users.idu',
            DB::raw("CONCAT( users.titulo, '', users.nombre, ' ',users.app, ' ', users.apm) AS responsable"),
            'actividades.turno',
            'actividades.comunicado',
            'actividades.asunto',
            'actividades.descripcion',
            'actividades.created_at AS fecha_creacion',
            'actividades.fecha_inicio',
            'actividades.fecha_fin',
            'actividades.importancia',
            'responsables_actividades.idreac',
            'actividades.idac',
            'actividades.idu_users AS creador_id',
            'areas.nombre AS area_responsable',
            'tipos_actividades.nombre AS tipo_actividad',
            'responsables_actividades.firma'
        )
        ->get()
        ->each(function($collection){

            $periodoInico = Carbon::parse($collection->fecha_inicio)->format('d-m-Y');
            $periodoFin = Carbon::parse($collection->fecha_fin)->format('d-m-Y');
            $collection->periodo = " $periodoInico al $periodoFin";

            $collection->creador = User::where('idu',$collection->creador_id)
                ->select('idu','titulo', 'nombre', 'app','apm')->first();

            $seguimiento = SeguimientosActividades::where('idreac_responsables_actividades',$collection->idreac)->get();
            $collection->numero_de_seguimiento = $seguimiento->count();
            $collection->porcentaje_seguimiento = $seguimiento->last()->porcentaje;//$seguimiento->avg('porcentaje');
            $collection->ultimo_seguimiento = $seguimiento->last();
            $collection->seguimiento = $seguimiento->last();
            return $collection;

        });
    }

    public function getActividadesEnProceso(User $user, TiposActividades $tiposActividades, $inicio, $fin){
      //Fecha de inicio y Fin
        $inicio = new Carbon($inicio);
        $fin = new Carbon($fin);
        $query = "SELECT t1.idreac
                FROM
                (SELECT idreac_responsables_actividades AS idreac ,ultimoporcentaje( idreac_responsables_actividades) AS ultimoporcentaje
                FROM seguimientos_actividades
                GROUP BY idreac_responsables_actividades) AS t1
                WHERE t1.ultimoporcentaje <100";
        $actividades = DB::select($query);

        if(count($actividades)<1) return  collect([]);
        $Actividades = [];
        foreach($actividades AS $actividad){
            array_push($Actividades,$actividad->idreac);
        }
        return User::where('idu', $user->idu)
        ->join('responsables_actividades', 'idu_users', 'users.idu')
        ->join('actividades', 'idac', 'responsables_actividades.idac_actividades')
        ->join('areas','areas.idar','actividades.idar_areas')
        ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
        //->where('responsables_actividades.fecha', null)
        ->where('actividades.idtac_tipos_actividades', $tiposActividades->idtac)
        ->where('actividades.fecha_inicio','>=', $inicio->format('Y-m-d'))
        ->where('actividades.fecha_fin','<=', $fin->format('Y-m-d'))
        ->whereIn('responsables_actividades.idreac', $Actividades)
        ->where('actividades.aprobacion', 1)
        ->select(
            'users.idu',
            DB::raw("CONCAT( users.titulo, '', users.nombre, ' ',users.app, ' ', users.apm) AS responsable"),
            'actividades.turno',
            'actividades.comunicado',
            'actividades.asunto',
            'actividades.descripcion',
            'actividades.created_at AS fecha_creacion',
            'actividades.fecha_inicio',
            'actividades.fecha_fin',
            'actividades.importancia',
            'responsables_actividades.idreac',
            'actividades.idac',
            'actividades.idu_users AS creador_id',
            'areas.nombre AS area_responsable',
            'tipos_actividades.nombre AS tipo_actividad',
            'responsables_actividades.firma'
        )
        ->get()
        ->each(function($collection){

            $periodoInico = Carbon::parse($collection->fecha_inicio)->format('d-m-Y');
            $periodoFin = Carbon::parse($collection->fecha_fin)->format('d-m-Y');
            $collection->periodo = " $periodoInico al $periodoFin";

            $collection->creador = User::where('idu',$collection->creador_id)
                ->select('idu','titulo', 'nombre', 'app','apm')->first();

            $seguimiento = SeguimientosActividades::where('idreac_responsables_actividades',$collection->idreac)->get();
            $collection->numero_de_seguimiento = $seguimiento->count();
            $collection->porcentaje_seguimiento = $seguimiento->avg('porcentaje');

            $collection->seguimiento = $seguimiento->last();
            return $collection;

        });
    }

    public function getActividadesSinEntregar(User $user, TiposActividades $tiposActividades, $inicio, $fin){
      //Fecha de inicio y Fin
        $inicio = new Carbon($inicio);
        $fin = new Carbon($fin);
        $actividades = ResponsablesActividades::where('idu_users', $user->idu)
            ->select('idreac')
            ->groupBy('responsables_actividades.idreac')
            ->get()
            ->each(function($responsableActividad){
                $responsableActividad->seguimiento =SeguimientosActividades::where('idreac_responsables_actividades',$responsableActividad->idreac)
                    ->groupBy('idreac_responsables_actividades')
                    ->get();
                return $responsableActividad;

            });
            foreach($actividades AS $key => $actividad){
                if($actividad->seguimiento->count()>0){
                    $actividades->forget($key);
                }
                unset($actividad['seguimiento']);
            }

        //return $actividades;
        if($actividades->count() < 1 ) {return $actividades;}
        return User::where('idu', $user->idu)
        ->join('responsables_actividades', 'idu_users', 'users.idu')
        ->join('actividades', 'idac', 'responsables_actividades.idac_actividades')
        ->join('areas','areas.idar','actividades.idar_areas')
        ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
        //->where('responsables_actividades.fecha', null)
        ->where('actividades.idtac_tipos_actividades', $tiposActividades->idtac)
        ->where('actividades.fecha_inicio','>=', $inicio->format('Y-m-d'))
        ->where('actividades.fecha_fin','<=', $fin->format('Y-m-d'))
        ->whereIn('responsables_actividades.idreac', $actividades)
        ->where('actividades.aprobacion', 1)
        ->select(
            'users.idu',
            DB::raw("CONCAT( users.titulo, '', users.nombre, ' ',users.app, ' ', users.apm) AS responsable"),
            'actividades.turno',
            'actividades.comunicado',
            'actividades.asunto',
            'actividades.descripcion',
            'actividades.created_at AS fecha_creacion',
            'actividades.fecha_inicio',
            'actividades.fecha_fin',
            'actividades.importancia',
            'responsables_actividades.idreac',
            'actividades.idac',
            'actividades.idu_users AS creador_id',
            'areas.nombre AS area_responsable',
            'tipos_actividades.nombre AS tipo_actividad',
            'responsables_actividades.firma'
        )
        ->get()
        ->each(function($collection){

            $periodoInico = Carbon::parse($collection->fecha_inicio)->format('d-m-Y');
            $periodoFin = Carbon::parse($collection->fecha_fin)->format('d-m-Y');
            $collection->periodo = " $periodoInico al $periodoFin";

            $collection->creador = User::where('idu',$collection->creador_id)
                ->select('idu','titulo', 'nombre', 'app','apm')->first();

            $seguimiento = SeguimientosActividades::where('idreac_responsables_actividades',$collection->idreac)->get();
            $collection->numero_de_seguimiento = $seguimiento->count();
            $collection->porcentaje_seguimiento = $seguimiento->avg('porcentaje');

            $collection->seguimiento = $seguimiento->last();
            return $collection;

        });
    }

    public function getActividadesConAcuseDeRecibido(User $user, TiposActividades $tiposActividades, $inicio, $fin){
      //Fecha de inicio y Fin
        $inicio = new Carbon($inicio);
        $fin = new Carbon($fin);
        return User::where('idu', $user->idu)
        ->join('responsables_actividades', 'idu_users', 'users.idu')
        ->join('actividades', 'idac', 'responsables_actividades.idac_actividades')
        ->join('areas','areas.idar','actividades.idar_areas')
        ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
        ->where('responsables_actividades.firma','!=', null)
        ->where('actividades.idtac_tipos_actividades', $tiposActividades->idtac)
        ->where('actividades.fecha_inicio','>=', $inicio->format('Y-m-d'))
        ->where('actividades.fecha_fin','<=', $fin->format('Y-m-d'))
        ->where('actividades.aprobacion', 1)
        ->select(
            'users.idu',
            DB::raw("CONCAT( users.titulo, '', users.nombre, ' ',users.app, ' ', users.apm) AS responsable"),
            'actividades.turno',
            'actividades.comunicado',
            'actividades.asunto',
            'actividades.descripcion',
            'actividades.created_at AS fecha_creacion',
            'actividades.fecha_inicio',
            'actividades.fecha_fin',
            'actividades.importancia',
            'responsables_actividades.idreac',
            'actividades.idac',
            'actividades.idu_users AS creador_id',
            'areas.nombre AS area_responsable',
            'tipos_actividades.nombre AS tipo_actividad',
            'responsables_actividades.firma'
        )
        ->get()
        ->each(function($collection){

            $periodoInico = Carbon::parse($collection->fecha_inicio)->format('d-m-Y');
            $periodoFin = Carbon::parse($collection->fecha_fin)->format('d-m-Y');
            $collection->periodo = " $periodoInico al $periodoFin";

            $collection->creador = User::where('idu',$collection->creador_id)
                ->select('idu','titulo', 'nombre', 'app','apm')->first();

            $seguimiento = SeguimientosActividades::where('idreac_responsables_actividades',$collection->idreac)->get();
            $collection->numero_de_seguimiento = $seguimiento->count();
            $collection->porcentaje_seguimiento = $seguimiento->avg('porcentaje');

            $collection->seguimiento = $seguimiento->last();
            return $collection;

        });
    }

    public function getActividadesSinAcuseDeRecibido(User $user, TiposActividades $tiposActividades, $inicio, $fin){
        $inicio = new Carbon($inicio);
        $fin = new Carbon($fin);
        return User::where('idu', $user->idu)
        ->join('responsables_actividades', 'idu_users', 'users.idu')
        ->join('actividades', 'idac', 'responsables_actividades.idac_actividades')
        ->join('areas','areas.idar','actividades.idar_areas')
        ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
        ->where('responsables_actividades.firma', null)
        ->where('actividades.idtac_tipos_actividades', $tiposActividades->idtac)
        ->where('actividades.fecha_inicio','>=', $inicio->format('Y-m-d'))
        ->where('actividades.fecha_fin','<=', $fin->format('Y-m-d'))
        ->where('actividades.aprobacion', 1)
        ->select(
            'users.idu',
            DB::raw("CONCAT( users.titulo, '', users.nombre, ' ',users.app, ' ', users.apm) AS responsable"),
            'actividades.turno',
            'actividades.comunicado',
            'actividades.asunto',
            'actividades.descripcion',
            'actividades.created_at AS fecha_creacion',
            'actividades.fecha_inicio',
            'actividades.fecha_fin',
            'actividades.importancia',
            'responsables_actividades.idreac',
            'actividades.idac',
            'actividades.idu_users AS creador_id',
            'areas.nombre AS area_responsable',
            'tipos_actividades.nombre AS tipo_actividad',
            'responsables_actividades.firma'
        )
        ->get()
        ->each(function($collection){

            $periodoInico = Carbon::parse($collection->fecha_inicio)->format('d-m-Y');
            $periodoFin = Carbon::parse($collection->fecha_fin)->format('d-m-Y');
            $collection->periodo = " $periodoInico al $periodoFin";

            $collection->creador = User::where('idu',$collection->creador_id)
                ->select('idu','titulo', 'nombre', 'app','apm')->first();

            $seguimiento = SeguimientosActividades::where('idreac_responsables_actividades',$collection->idreac)->get();
            $collection->numero_de_seguimiento = $seguimiento->count();
            $collection->porcentaje_seguimiento = $seguimiento->avg('porcentaje');

            $collection->seguimiento = $seguimiento->last();
            return $collection;

        });
    }

    public function getActividadesPorTipoArea(User $user, Request $request){
      //Fecha de inicio y Fin
        $inicio = $request->inicio;
        $fin = $request->fin;
        $inicio = new Carbon($inicio);
        $fin = new Carbon($fin);
        return [['actividades' => User::where('idu', $user->idu)
        ->join('responsables_actividades', 'idu_users', 'users.idu')
        ->join('actividades', 'idac', 'responsables_actividades.idac_actividades')
        ->join('areas','areas.idar','actividades.idar_areas')
        ->join('tipos_actividades','tipos_actividades.idtac','actividades.idtac_tipos_actividades')
        //->where('responsables_actividades.fecha', null)
        ->where('tipos_actividades.nombre', $request->tipo_area)
        ->where('actividades.fecha_inicio','>=', $inicio->format('Y-m-d'))
        ->where('actividades.fecha_fin','<=', $fin->format('Y-m-d'))
        ->where('actividades.aprobacion', 1)
        ->select(
            'users.idu',
            DB::raw("CONCAT( users.titulo, '', users.nombre, ' ',users.app, ' ', users.apm) AS responsable"),
            'actividades.turno',
            'actividades.comunicado',
            'actividades.asunto',
            'actividades.descripcion',
            'actividades.created_at AS fecha_creacion',
            'actividades.fecha_inicio',
            'actividades.fecha_fin',
            'actividades.importancia',
            'responsables_actividades.idreac',
            'actividades.idac',
            'actividades.idu_users AS creador_id',
            'areas.nombre AS area_responsable',
            'tipos_actividades.nombre AS tipo_actividad',
            'responsables_actividades.firma'
        )
        ->get()
        ->each(function($collection){

            $periodoInico = Carbon::parse($collection->fecha_inicio)->format('d-m-Y');
            $periodoFin = Carbon::parse($collection->fecha_fin)->format('d-m-Y');
            $collection->periodo = " $periodoInico al $periodoFin";

            $collection->creador = User::where('idu',$collection->creador_id)
                ->select('idu','titulo', 'nombre', 'app','apm')->first();

            $seguimiento = SeguimientosActividades::where('idreac_responsables_actividades',$collection->idreac)->get();
            $collection->numero_de_seguimiento = $seguimiento->count();
            $collection->porcentaje_seguimiento = $seguimiento->avg('porcentaje');

            $collection->seguimiento = $seguimiento->last();
            return $collection;

        })]];
    }

    private function getActividadesCompletadasEnTiempo(User $user, TiposActividades $tiposActividades, $inicio, $fin){
        $actividadesCompletadas = $this->getActividadesCompletadas($user,$tiposActividades,$inicio,$fin);
        foreach($actividadesCompletadas AS $key =>$actividadCompletada){
            $seguimiento = new Carbon($actividadCompletada->ultimo_seguimiento->created_at);
            $fechaFin = new Carbon($actividadCompletada->fecha_fin);
            $seguimiento = $seguimiento->format('Y-m-d');
            $fechaFin = $fechaFin->format('Y-m-d');

            if($seguimiento > $fechaFin){
                $actividadesCompletadas->forget($key);
            }
        }
        return $actividadesCompletadas;
    }

    public function getActividadesCompletadasFueraDeTiempo(User $user, TiposActividades $tiposActividades, $inicio, $fin){

        $actividadesCompletadas = $this->getActividadesCompletadas($user,$tiposActividades,$inicio,$fin);

        foreach($actividadesCompletadas AS $key =>$actividadCompletada){
            $seguimiento = new Carbon($actividadCompletada->ultimo_seguimiento->created_at);
            $fechaFin = new Carbon($actividadCompletada->fecha_fin);
            $seguimiento = $seguimiento->format('Y-m-d');
            $fechaFin = $fechaFin->format('Y-m-d');

            if(!($seguimiento > $fechaFin)){
                $actividadesCompletadas->forget($key);
            }
        }
        return $actividadesCompletadas;
    }

    public function getActividadesEnProcesoEnTiempo(User $user, TiposActividades $tiposActividades, $inicio, $fin){
        $actividadesCompletadas = $this->getActividadesEnProceso($user,$tiposActividades,$inicio,$fin);
        foreach($actividadesCompletadas AS $key =>$actividadCompletada){
            $seguimiento = new Carbon($actividadCompletada->seguimiento->created_at);
            $fechaFin = new Carbon($actividadCompletada->fecha_fin);
            $seguimiento = $seguimiento->format('Y-m-d');
            $fechaFin = $fechaFin->format('Y-m-d');

            if($seguimiento > $fechaFin){
                $actividadesCompletadas->forget($key);
            }
        }
        return $actividadesCompletadas;
    }

    public function getActividadesEnProcesoFueraDeTiempo(User $user, TiposActividades $tiposActividades, $inicio, $fin){
        $actividadesCompletadas = $this->getActividadesEnProceso($user,$tiposActividades,$inicio,$fin);
        foreach($actividadesCompletadas AS $key =>$actividadCompletada){
            $seguimiento = new Carbon($actividadCompletada->seguimiento->created_at);
            $fechaFin = new Carbon($actividadCompletada->fecha_fin);
            $seguimiento = $seguimiento->format('Y-m-d');
            $fechaFin = $fechaFin->format('Y-m-d');

            if(!($seguimiento > $fechaFin)){
                $actividadesCompletadas->forget($key);
            }
        }
        return $actividadesCompletadas;
    }

}
