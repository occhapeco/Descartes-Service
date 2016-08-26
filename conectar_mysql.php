<?php

	$servidor = "localhost";
	$banco = "descartes";
	$usuario = "root";
	$senhaa = "";

	$conexao = new mysqli($servidor, $usuario, $senhaa, $banco);

	if (mysqli_connect_errno())
		trigger_error(mysqli_connect_error());
 ?>
