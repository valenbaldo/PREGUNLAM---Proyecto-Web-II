<?php

class LoginModel
{

    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }


    public function nuevo($nombre, $apellido, $usuario, $nacimiento, $imagen, $sexo, $mail, $pais, $ciudad, $contraseña, $token)
    {

        $sql = "INSERT INTO usuarios (nombre, apellido, usuario, fecha_nacimiento, imagen, mail, contraseña, token)
                VALUES ('$nombre', '$apellido', '$usuario', '$nacimiento', '$imagen', '$mail', '$contraseña', '$token')";
        $this->conexion->query($sql);

        $data = $this->conexion->query("SELECT id_usuario FROM usuarios WHERE mail = '$mail'");
        $id_usuario = $data[0]["id_usuario"];


        $sqlPais = "INSERT INTO ubicacion (pais, ciudad, id_usuario)
                    VALUES ('$pais', '$ciudad', '$id_usuario')";
        $this->conexion->query($sqlPais);

        $sqlSexo = "INSERT INTO sexo (sexo, id_usuario)
                    VALUES ('$sexo', '$id_usuario')";
        $this->conexion->query($sqlSexo);
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