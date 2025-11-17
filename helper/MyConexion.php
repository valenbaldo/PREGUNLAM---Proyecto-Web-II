
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

        if (is_object($result)) {
            if ($result->num_rows > 0) {
                return $result->fetch_all(MYSQLI_ASSOC);
            }
        }else{
            return [];
        }
        return null;
    }

    public function execute($sql){
        $this->conexion->query($sql);

    }
}
