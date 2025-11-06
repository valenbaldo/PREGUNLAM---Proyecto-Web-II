<?php

class MyConexion
{

    private $conexion;

    public function __construct($server, $user, $pass, $database)
    {
        $this->conexion = new mysqli($server, $user, $pass, $database);
        if ($this->conexion->error) { die("Error en la conexión: " . $this->conexion->error); }
    }

    public function query($sql)
    {
        $result = $this->conexion->query($sql);

        if ($this->conexion->error) {
            error_log("Error MySQL en query(): " . $this->conexion->error . " - SQL: " . $sql);
            return false;
        }

        if (is_object($result)) {
            if ($result->num_rows > 0) {
                return $result->fetch_all(MYSQLI_ASSOC);
            } else {
                return [];
            }
        } else {
            // Para INSERT, UPDATE, DELETE que no devuelven resultados
            return true;
        }
    }

    public function execute($sql){
        $result = $this->conexion->query($sql);
        if ($this->conexion->error) {
            error_log("Error MySQL en execute(): " . $this->conexion->error . " - SQL: " . $sql);
            return false;
        }
        return true;
    }
}