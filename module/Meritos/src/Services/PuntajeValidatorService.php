<?php
namespace Meritos\Services;

class PuntajeValidatorService {
    
    private $adapter;
    
    // Límites por categoría
    const LIMITES = [
        'premios' => 2,
        'investigaciones' => 6,
        'formacion_academica' => 10,
        'cargos' => 4,
        'capacitacion_profesional' => 8
    ];
    
    const LIMITE_TOTAL = 30;
    
    public function __construct($adapter) {
        $this->adapter = $adapter;
    }
    
    /**
     * Obtener puntaje actual por categoría de un docente
     */
    public function obtenerPuntajeActual($id_usuario, $categoria) {
        // Obtener el año actual o el período activo
        $year = date('Y');
        
        $sql = "SELECT {$categoria} as puntaje FROM puntos 
                WHERE id_usuario = ? AND year = ?";
        
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute([$id_usuario, $year]);
        $row = $result->current();
        
        return (float)($row['puntaje'] ?? 0);
    }
    
    /**
     * Obtener todos los puntajes de un docente
     */
    public function obtenerTodosPuntajes($id_usuario) {
        $year = date('Y');
        
        $sql = "SELECT premios, investigaciones, formacion_academica, cargos, capacitacion_profesional 
                FROM puntos 
                WHERE id_usuario = ? AND year = ?";
        
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute([$id_usuario, $year]);
        $row = $result->current();
        
        if (!$row) {
            return [
                'premios' => 0,
                'investigaciones' => 0,
                'formacion_academica' => 0,
                'cargos' => 0,
                'capacitacion_profesional' => 0
            ];
        }
        
        return [
            'premios' => (float)$row['premios'],
            'investigaciones' => (float)$row['investigaciones'],
            'formacion_academica' => (float)$row['formacion_academica'],
            'cargos' => (float)$row['cargos'],
            'capacitacion_profesional' => (float)$row['capacitacion_profesional']
        ];
    }
    
    /**
     * Obtener puntaje total de un docente
     */
    public function obtenerPuntajeTotal($id_usuario) {
        $puntajes = $this->obtenerTodosPuntajes($id_usuario);
        
        return array_sum($puntajes);
    }
    
    /**
     * Verificar si una categoría ha alcanzado su límite
     */
    public function categoriaLimiteAlcanzado($id_usuario, $categoria) {
        $puntajeActual = $this->obtenerPuntajeActual($id_usuario, $categoria);
        $limite = self::LIMITES[$categoria] ?? 0;
        
        return $puntajeActual >= $limite;
    }
    
    /**
     * Verificar si el docente ha alcanzado el límite total
     */
    public function limiteTotalAlcanzado($id_usuario) {
        return $this->obtenerPuntajeTotal($id_usuario) >= self::LIMITE_TOTAL;
    }
    
    /**
     * Determinar el estado que debería tener un mérito
     */
    public function determinarEstadoMerito($id_usuario, $categoria, $puntos_merito) {
        $puntajeCategoria = $this->obtenerPuntajeActual($id_usuario, $categoria);
        $puntajeTotal = $this->obtenerPuntajeTotal($id_usuario);
        $limiteCategoria = self::LIMITES[$categoria] ?? 0;
        
        // Si ya alcanzó el límite de la categoría
        if ($puntajeCategoria >= $limiteCategoria) {
            return 'Ingresada - Límite Alcanzado';
        }
        
        // Si ya alcanzó el límite total
        if ($puntajeTotal >= self::LIMITE_TOTAL) {
            return 'Ingresada - Sin Efecto';
        }
        
        // Si con este mérito se pasaría del límite de categoría
        if (($puntajeCategoria + $puntos_merito) > $limiteCategoria) {
            return 'Ingresada - Límite Alcanzado';
        }
        
        // Si con este mérito se pasaría del límite total
        if (($puntajeTotal + $puntos_merito) > self::LIMITE_TOTAL) {
            return 'Ingresada - Sin Efecto';
        }
        
        return 'Ingresada';
    }
    
    /**
     * Obtener información completa del docente para debug
     */
    public function obtenerInformacionCompleta($id_usuario) {
        $puntajes = $this->obtenerTodosPuntajes($id_usuario);
        $total = $this->obtenerPuntajeTotal($id_usuario);
        
        return [
            'puntajes_por_categoria' => $puntajes,
            'puntaje_total' => $total,
            'limites' => self::LIMITES,
            'limite_total' => self::LIMITE_TOTAL,
            'categorias_limite_alcanzado' => [
                'premios' => $this->categoriaLimiteAlcanzado($id_usuario, 'premios'),
                'investigaciones' => $this->categoriaLimiteAlcanzado($id_usuario, 'investigaciones'),
                'formacion_academica' => $this->categoriaLimiteAlcanzado($id_usuario, 'formacion_academica'),
                'cargos' => $this->categoriaLimiteAlcanzado($id_usuario, 'cargos'),
                'capacitacion_profesional' => $this->categoriaLimiteAlcanzado($id_usuario, 'capacitacion_profesional'),
            ],
            'limite_total_alcanzado' => $this->limiteTotalAlcanzado($id_usuario)
        ];
    }
    
    /**
     * Recalcular estados de todos los méritos de un docente
     */
    public function recalcularEstadosDocente($id_usuario) {
        $tablas = [
            'premios' => 'premios',
            'investigaciones' => 'investigaciones', 
            'formacion_academica' => 'formacion_academica',
            'cargos' => 'cargos',
            'capacitacion_profesional' => 'capacitacion_profesional'
        ];
        
        foreach($tablas as $categoria => $tabla) {
            // Obtener méritos ingresados y con límite alcanzado
            $sql = "SELECT id, puntos FROM {$tabla} 
                    WHERE id_usuario = ? 
                    AND id_estado IN (
                        SELECT id_estado FROM estado 
                        WHERE nombre_estado IN ('Ingresada', 'Ingresada - Límite Alcanzado', 'Ingresada - Sin Efecto')
                    )
                    ORDER BY created_at ASC";
            
            $statement = $this->adapter->createStatement($sql);
            $result = $statement->execute([$id_usuario]);
            
            foreach($result as $merito) {
                $nuevoEstado = $this->determinarEstadoMerito($id_usuario, $categoria, $merito['puntos']);
                
                // Obtener ID del estado
                $sqlEstado = "SELECT id_estado FROM estado WHERE nombre_estado = ?";
                $stmtEstado = $this->adapter->createStatement($sqlEstado);
                $resultEstado = $stmtEstado->execute([$nuevoEstado]);
                $estadoRow = $resultEstado->current();
                
                if ($estadoRow) {
                    $idEstado = $estadoRow['id_estado'];
                    
                    // Actualizar el mérito
                    $sqlUpdate = "UPDATE {$tabla} SET id_estado = ? WHERE id = ?";
                    $stmtUpdate = $this->adapter->createStatement($sqlUpdate);
                    $stmtUpdate->execute([$idEstado, $merito['id']]);
                }
            }
        }
    }
}