<?php

class LoginModel
{

    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }


    public function nuevo($nombre, $apellido, $mail, $pais, $ciudad, $contraseña, $token)
    {

        $sql = "INSERT INTO usuarios (nombre, apellido, mail, pais, ciudad, contraseña, token)
                VALUES ('$nombre', '$apellido', '$mail', '$pais', '$ciudad', '$contraseña', '$token')";
        $this->conexion->query($sql);
    }

    public function obtenerUsuario($mail)
    {
        $sql = "SELECT * FROM usuarios WHERE mail = '$mail'";
        $usuarioEncontrado = $this->conexion->query($sql);
        if ($usuarioEncontrado) {
            return $usuarioEncontrado;
        }
        return null;
    }

    public function verificarMail($tokenRecibido)
    {
        $sql = "UPDATE usuarios SET verificado = true WHERE token = '$tokenRecibido'";
        $this->conexion->execute($sql);
    }
}