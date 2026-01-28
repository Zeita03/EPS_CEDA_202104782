<?php
namespace Meritos\Services;

class PuntajeValidatorService {
    
    private $adapter;
    private $periodoActual = null;
    
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
     * Establecer el período actual para todas las consultas
     */
    public function setPeriodoActual($periodo_id) {
        $this->periodoActual = $periodo_id;
    }
    
    /**
     * Obtener puntaje actual por categoría de un docente en un período específico
     */
    public function obtenerPuntajeActual($id_usuario, $categoria, $periodo_id = null) {
        $periodo = $periodo_id ?: $this->periodoActual;
        
        if (!$periodo) {
            return 0;
        }
        
        $sql = "SELECT {$categoria} as puntaje FROM puntos 
                WHERE id_usuario = ? AND id_periodo = ?";
        
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute([$id_usuario, $periodo]);
        $row = $result->current();
        
        return (float)($row['puntaje'] ?? 0);
    }
    
    /**
     * Obtener todos los puntajes de un docente en un período específico
     */
    public function obtenerTodosPuntajes($id_usuario, $periodo_id = null) {
        $periodo = $periodo_id ?: $this->periodoActual;
        
        if (!$periodo) {
            return [
                'premios' => 0,
                'investigaciones' => 0,
                'formacion_academica' => 0,
                'cargos' => 0,
                'capacitacion_profesional' => 0
            ];
        }
        
        $sql = "SELECT premios, investigaciones, formacion_academica, cargos, capacitacion_profesional 
                FROM puntos 
                WHERE id_usuario = ? AND id_periodo = ?";
        
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute([$id_usuario, $periodo]);
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
     * Obtener puntaje total de un docente en un período específico
     */
    public function obtenerPuntajeTotal($id_usuario, $periodo_id = null) {
        $puntajes = $this->obtenerTodosPuntajes($id_usuario, $periodo_id);
        
        return array_sum($puntajes);
    }
    
    /**
     * Verificar si una categoría ha alcanzado su límite en un período específico
     */
    public function categoriaLimiteAlcanzado($id_usuario, $categoria, $periodo_id = null) {
        $puntajeActual = $this->obtenerPuntajeActual($id_usuario, $categoria, $periodo_id);
        $limite = self::LIMITES[$categoria] ?? 0;
        
        return $puntajeActual >= $limite;
    }
    
    /**
     * Verificar si el docente ha alcanzado el límite total en un período específico
     */
    public function limiteTotalAlcanzado($id_usuario, $periodo_id = null) {
        return $this->obtenerPuntajeTotal($id_usuario, $periodo_id) >= self::LIMITE_TOTAL;
    }
    
    /**
     * Determinar el estado que debería tener un mérito en un período específico
     */
    public function determinarEstadoMerito($id_usuario, $categoria, $puntos_merito, $periodo_id = null) {
        $puntajeCategoria = $this->obtenerPuntajeActual($id_usuario, $categoria, $periodo_id);
        $puntajeTotal = $this->obtenerPuntajeTotal($id_usuario, $periodo_id);
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
     * Obtener información completa del docente para debug en un período específico
     */
    public function obtenerInformacionCompleta($id_usuario, $periodo_id = null) {
        $puntajes = $this->obtenerTodosPuntajes($id_usuario, $periodo_id);
        $total = $this->obtenerPuntajeTotal($id_usuario, $periodo_id);
        
        return [
            'puntajes_por_categoria' => $puntajes,
            'puntaje_total' => $total,
            'periodo_usado' => $periodo_id ?: $this->periodoActual,
            'limites' => self::LIMITES,
            'limite_total' => self::LIMITE_TOTAL,
            'categorias_limite_alcanzado' => [
                'premios' => $this->categoriaLimiteAlcanzado($id_usuario, 'premios', $periodo_id),
                'investigaciones' => $this->categoriaLimiteAlcanzado($id_usuario, 'investigaciones', $periodo_id),
                'formacion_academica' => $this->categoriaLimiteAlcanzado($id_usuario, 'formacion_academica', $periodo_id),
                'cargos' => $this->categoriaLimiteAlcanzado($id_usuario, 'cargos', $periodo_id),
                'capacitacion_profesional' => $this->categoriaLimiteAlcanzado($id_usuario, 'capacitacion_profesional', $periodo_id),
            ],
            'limite_total_alcanzado' => $this->limiteTotalAlcanzado($id_usuario, $periodo_id)
        ];
    }
    
    /**
     * Recalcular estados de todos los méritos de un docente en un período específico
     */
    public function recalcularEstadosDocente($id_usuario, $periodo_id = null) {
        $periodo = $periodo_id ?: $this->periodoActual;
        
        if (!$periodo) {
            return;
        }
        
        $tablas = [
            'premios' => 'premios',
            'investigaciones' => 'investigaciones', 
            'formacion_academica' => 'formacion_academica',
            'cargos' => 'cargos',
            'capacitacion_profesional' => 'capacitacion_profesional'
        ];
        
        foreach($tablas as $categoria => $tabla) {
            // Obtener méritos ingresados del período específico
            $sql = "SELECT id, puntos FROM {$tabla} 
                    WHERE id_usuario = ? 
                    AND id_periodo = ?
                    AND id_estado IN (
                        SELECT id_estado FROM estado 
                        WHERE nombre_estado IN ('Ingresada', 'Ingresada - Límite Alcanzado', 'Ingresada - Sin Efecto')
                    )
                    ORDER BY created_at ASC";
            
            $statement = $this->adapter->createStatement($sql);
            $result = $statement->execute([$id_usuario, $periodo]);
            
            foreach($result as $merito) {
                $nuevoEstado = $this->determinarEstadoMerito($id_usuario, $categoria, $merito['puntos'], $periodo);
                
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
    

    /**
     * Obtener puntaje actual por categoría usando año (LEGACY)
     * legacy 
    */
    public function obtenerPuntajeActualPorAño($id_usuario, $categoria, $year = null) {
        $year = $year ?: date('Y');
        
        $sql = "SELECT {$categoria} as puntaje FROM puntos 
                WHERE id_usuario = ? AND year = ?";
        
        $statement = $this->adapter->createStatement($sql);
        $result = $statement->execute([$id_usuario, $year]);
        $row = $result->current();
        
        return (float)($row['puntaje'] ?? 0);
    }
}