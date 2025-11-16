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
            return true;
        }
    }

    public function execute($sql, $params = [])
    {
        if (empty($params)) {
            $result = $this->conexion->query($sql);
        } else {
            $stmt = $this->conexion->prepare($sql);

            if ($stmt === false) {
                error_log("Error MySQL en prepare(): " . $this->conexion->error . " - SQL: " . $sql);
                return false;
            }

            $tipos = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $tipos .= 'i';
                } elseif (is_float($param)) {
                    $tipos .= 'd';
                } else {
                    $tipos .= 's';
                }
            }

            $bind_params = array_merge([$tipos], $params);
            call_user_func_array([$stmt, 'bind_param'], $this->refValues($bind_params));

            $result = $stmt->execute();

            if ($stmt->error) {
                error_log("Error MySQL en execute() stmt: " . $stmt->error . " - SQL: " . $sql);
                $stmt->close();
                return false;
            }

            $stmt->close();
        }
        return true;
    }
    private function refValues($arr)
    {
        if (strnatcmp(phpversion(), '5.3') >= 0) {
            $refs = [];
            foreach ($arr as $key => $value) {
                $refs[$key] = &$arr[$key];
            }
            return $refs;
        }
        return $arr;
    }
}