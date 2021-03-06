<?php

namespace App\Http\Controllers;

use App\Models\Areas;
use App\Models\TiposAreas;
use Illuminate\Http\Request;

class AreasController extends Controller
{
    /**
     * Vista para mostrar un listado de areas.
     */
    public function index()
    {
        $areas = Areas::query()
                        ->join('tipos_areas', 'tipos_areas.idtar', '=' , 'areas.idtar')
                        ->select(
                                'areas.idar',
                                'areas.nombre',
                                'areas.activo',
                                'tipos_areas.nombre as idtar')
                                ->orderby('nombre', 'ASC')
                        ->get();

        $tipos_areas = TiposAreas::all();
        $array = array();

        function btn($idar, $activo){
            if($activo == 1){
                $botones = "<a href=\"#eliminar\" class=\"btn btn-danger mt-1\" onclick=\"formSubmit('eliminar-area-$idar')\"><i class='fas fa-power-off'></i></a>"
                         . "<a href=". route('areas.edit', $idar ) ." class=\"btn btn-primary mt-1\"> <i class='fas fa-edit'></i> </a>";
            } else {
                $botones = "<a href=\"#activar\" class=\"btn btn-info mt-1\" onclick=\"formSubmit('eliminar-area-$idar')\"><i class='fas fa-lightbulb'></i></a>";
            }

            return $botones;
        }

        foreach ($areas as $area){

            array_push($array, array(
                'idar'        =>$area->idar,
                'nombre'      =>$area->nombre,
                'idtar'       =>$area->idtar,
                'activo'      => ($area->activo == 1) ? "Si" : "No",
                'operaciones' => btn($area->idar, $area->activo)
            ));
        }

        $json = json_encode($array);

        return view('areas.index', compact('json','areas', 'tipos_areas'));
    }

    /**
     * Vista que muestra un formulario para crear un area.
     */
    public function create()
    {
        $tipos_areas = TiposAreas::all();
        return view('areas.create', compact('tipos_areas'));
    }

    /**
     * Guardar un area.
     */
    public function store(Request $request)
    {
        $request->validate([
                    'nombre'  => ['required', 'string', "regex:/^[a-z,A-Z,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,
                                ??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,
                                ??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,
                                ??,??,??,??,??,??,??,??,???,??, ]*$/"],
                    'idtar'   => ['required', 'integer', 'exists:tipos_areas,idtar'],
            ]);

        $guardar = Areas::query()
                        ->create([
                        'nombre' => $request->nombre,
                        'idtar'  => $request->idtar
            ]);
        return redirect()->route('areas.index')->with('mensaje', 'El ??rea se ha creado exitosamente');
    }

    /**
     * Vista para mostrar una sola area .
     */
    public function show($idar)
    {
        if ($idar){
            $area = Areas::query()
                         ->where('idar', $idar)
                         ->first();
            if ($area) {
                return view('areas', compact('areas'));
            } else {
                abort(404);
            }
        } else {
            abort(404);
        }
    }

    /**
     * Vista que muestra un formulario para editar un area.
     */
    public function edit($idar)
    {
            $tipos_areas = TiposAreas::all();
            $areas = Areas::query()
                         ->where('idar', $idar)
                         ->get();
                return view('areas.edit', compact('areas', 'tipos_areas'));

    }

    /**
     * Actualiza un area.
     */
    public function update(Request $request, $idar)
    {
        if($idar) {
            $area = Areas::query()
                         ->where('idar', $idar)
                         ->first();
            if($area){
                $request->validate([
                    'nombre'  => ['nullable', 'string', "regex:/^[a-z,A-Z,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,
                                ??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,
                                ??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,??,
                                ??,??,??,??,??,??,??,??,???,??, ]*$/"],
                    'idtar'   => ['nullable', 'integer', 'exists:tipos_areas,idtar'],
                    'activo' => ['nullable', 'boolean']
                ]);

                $actualizar = $area->update([
                    'nombre' => $request->nombre,
                    'idtar'  => $request->idtar,
                    'activo' => $request->activo
                ]);
                return redirect()->route('areas.index')->with('mensaje', 'El ??rea se ha actualizado exitosamente');
            } else {
                abort(404);
                }
            } else {
                abort(404);
        }
    }



    /**
     * Elimina un area.
     */
    public function destroy($idar)
    {
        if ($idar){
            $area = Areas::where('idar', $idar)
                         ->first();
            if ($area){
                $eliminar = $area->update([
                    'activo' => ($area->activo == 1) ? 0 : 1
                ]);
                return redirect()->route('areas.index')->with('mensaje', 'Su estado ha cambiado');
            } else {
                abort(404);
            }
        } else {
            abort(404);
        }
    }
}
