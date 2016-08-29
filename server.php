<?php
	require_once('lib/nusoap.php');

	$server = new soap_server;
	$server->configureWSDL('service.descartes','urn:service.descartes');
	$namespace = 'urn:service.descartes';
	$server->wsdl->schemaTargetNamespace = $namespace;

	function validar_cpf($cpf){
		// Verifica se um número foi informado
	    if(empty($cpf))
	        return false;

	    // Elimina possivel mascara
	    $cpf = ereg_replace('[^0-9]', '', $cpf);
	    $cpf = str_pad($cpf, 11, '0', STR_PAD_LEFT);

	    // Verifica se o numero de digitos informados é igual a 11
	    if (strlen($cpf) != 11)
	        return false;

	    // Verifica se nenhuma das sequências invalidas abaixo
	    // foi digitada. Caso afirmativo, retorna falso
	    else if($cpf == '00000000000' ||
		        $cpf == '11111111111' ||
		        $cpf == '22222222222' ||
		        $cpf == '33333333333' ||
		        $cpf == '44444444444' ||
		        $cpf == '55555555555' ||
		        $cpf == '66666666666' ||
		        $cpf == '77777777777' ||
		        $cpf == '88888888888' ||
	        	$cpf == '99999999999')
	        return false;
	     // Calcula os digitos verificadores para verificar se o
	    else {

	        for ($t = 9; $t < 11; $t++) {

	            for ($d = 0, $c = 0; $c < $t; $c++) {
	                $d += $cpf{$c} * (($t + 1) - $c);
	            }
	            $d = ((10 * $d) % 11) % 10;
	            if ($cpf{$c} != $d) {
	                return false;
	            }
	        }

	        return true;
	    }
	}

	function validar_cnpj($cnpj){
		$cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
		// Valida tamanho
		if (strlen($cnpj) != 14)
			return false;
		// Valida primeiro dígito verificador
		for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++)
		{
			$soma += $cnpj{$i} * $j;
			$j = ($j == 2) ? 9 : $j - 1;
		}
		$resto = $soma % 11;
		if ($cnpj{12} != ($resto < 2 ? 0 : 11 - $resto))
			return false;
		// Valida segundo dígito verificador
		for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++)
		{
			$soma += $cnpj{$i} * $j;
			$j = ($j == 2) ? 9 : $j - 1;
		}
		$resto = $soma % 11;
		return $cnpj{13} == ($resto < 2 ? 0 : 11 - $resto);
	}

	//-------Classes do server-------//

	// Classe da tabela tipo_lixo //
	class tipo_lixo {
	    function insert($nome) {
	    	$nome = ereg_replace("[#'/*\|`]", '',$nome);
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("INSERT INTO tipo_lixo VALUES(NULL,'$nome')");
	    	$id = 0;
	    	if ($query == true)
	    		$id = $conexao->insert_id;
			$conexao->close();
	      	return $id;
	    }
	    function update($id,$nome) {
	    	$nome = ereg_replace("[#'/*\|`]", '',$nome);
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("SELECT * FROM tipo_lixo WHERE id = $id");
	    	$retorno = false;
			if (mysqli_num_rows($query) == 1)
			{
		    	$query = $conexao->query("UPDATE tipo_lixo SET nome = '$nome' WHERE id = $id");
				$retorno = true;
			}
			$conexao->close();
	     	return $retorno;
	    }
	    function delete($id) {
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("DELETE FROM tipo_lixo_has_ponto WHERE tipo_lixo_id = $id");
	    	$query = $conexao->query("DELETE FROM agendamento_has_tipo_lixo WHERE tipo_lixo_id = $id");
	    	$query = $conexao->query("DELETE FROM tipo_lixo WHERE id = $id");
	    	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM tipo_lixo WHERE id = $id");
	    	if (mysqli_num_rows($query) == 0)
	    		$retorno = true;
			$conexao->close();
	  		return $retorno;
	    }
	    function select_by_id($id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM tipo_lixo WHERE id = $id");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
		function select($condicoes) {
			require_once("conectar_mysql.php");
			if ($condicoes != NULL)
				$condicoes = "WHERE " . $condicoes;
			$query = $conexao->query("SELECT * FROM tipo_lixo $condicoes");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
	}
	// Registro dos métodos da classe tipo_lixo //
	$server->register('tipo_lixo.insert', array('nome' => 'xsd:string'), array('return' => 'xsd:integer'),$namespace,false,'rpc','encoded','Insere um registro na table tipo_lixo (retorna o id do registro inserido).');
	$server->register('tipo_lixo.update', array('id' => 'xsd:integer','nome' => 'xsd:string'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Altera um registro da tabela tipo_lixo.');
	$server->register('tipo_lixo.delete', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Deleta um registro da tabela tipo_lixo.');
	$server->register('tipo_lixo.select_by_id', array('id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tabela tipo_lixo por id (retorna json).');
	$server->register('tipo_lixo.select', array('condicoes' => 'xsd:string'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tabela tipo_lixo com condições definidas ou indefinidas (retorna json).');

	// Classe da tabela endereco //
	class endereco {
		function insert($rua,$num,$complemento,$cep,$bairro,$uf,$cidade,$pais,$latitude,$longitude) {
			$rua = ereg_replace("[#'/*\|`]", '',$rua);
			$complemento = ereg_replace("[#'/*\|`]", '',$complemento);
			$cep = ereg_replace("[#'/*\|`]", '',$cep);
			$bairro = ereg_replace("[#'/*\|`]", '',$bairro);
			$uf = ereg_replace("[#'/*\|`]", '',$uf);
			$cidade = ereg_replace("[#'/*\|`]", '',$cidade);
			$pais = ereg_replace("[#'/*\|`]", '',$pais);
			$id = 0;
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("INSERT INTO endereco VALUES(NULL,'$rua','$num','$complemento','$cep','$bairro','$uf','$cidade','$pais',$latitude,$longitude)");
	    	if ($query == true)
	    		$id = $conexao->insert_id;
			$conexao->close();
	      	return $id;
	    }
	    function update($id,$rua,$num,$complemento,$cep,$bairro,$uf,$cidade,$pais,$latitude,$longitude) {
	    	$rua = ereg_replace("[#'/*\|`]", '',$rua);
			$complemento = ereg_replace("[#'/*\|`]", '',$complemento);
			$cep = ereg_replace("[#'/*\|`]", '',$cep);
			$bairro = ereg_replace("[#'/*\|`]", '',$bairro);
			$uf = ereg_replace("[#'/*\|`]", '',$uf);
			$cidade = ereg_replace("[#'/*\|`]", '',$cidade);
			$pais = ereg_replace("[#'/*\|`]", '',$pais);
			require_once("conectar_mysql.php");
	    	$query = $conexao->query("SELECT * FROM endereco WHERE id = $id");
	    	$retorno = false;
			if (mysqli_num_rows($query) == 1)
			{
		    	$query = $conexao->query("UPDATE endereco SET rua = '$rua',num = '$num',complemento = '$complemento',cep = '$cep',bairro = '$bairro',uf = '$uf',cidade = '$cidade',pais = '$pais',latitude = $latitude,longitude = $longitude WHERE id = $id");
				$query = $conexao->query("SELECT * FROM endereco WHERE id = $id");
				$row = mysqli_fetch_assoc($query);
				$retorno = true;
			}
			$conexao->close();
	     	return $retorno;
	    }
	    function delete($id) {
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("DELETE FROM endereco_has_ponto WHERE endereco_id = $id");
	    	$query = $conexao->query("DELETE FROM usuario_has_endereco WHERE endereco_id = $id");
	    	$query = $conexao->query("DELETE FROM endereco WHERE id = $id");
	    	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM endereco WHERE id = $id");
	    	if (mysqli_num_rows($query) == 0)
	    		$retorno = true;
			$conexao->close();
	  		return $retorno;
	    }
	    function select_by_id($id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM endereco WHERE id = $id");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
		function select($condicoes) {
			require_once("conectar_mysql.php");
			if ($condicoes != NULL)
				$condicoes = "WHERE " . $condicoes;
			$query = $conexao->query("SELECT * FROM endereco $condicoes");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
	}
	// Registro dos métodos da classe endereco //
	$server->register('endereco.insert', array('rua' => 'xsd:string','num' => 'xsd:string','complemento' => 'xsd:string','cep' => 'xsd:string','cep' => 'xsd:string','bairro' => 'xsd:string','uf' => 'xsd:string','cidade' => 'xsd:string','pais' => 'xsd:string','latitude' => 'xsd:double','longitude' => 'xsd:double'), array('return' => 'xsd:integer'),$namespace,false,'rpc','encoded','Insere um registro na table endereco (retorna o id do registro inserido).');
	$server->register('endereco.update', array('id' => 'xsd:integer','rua' => 'xsd:string','num' => 'xsd:string','complemento' => 'xsd:string','cep' => 'xsd:string','cep' => 'xsd:string','bairro' => 'xsd:string','uf' => 'xsd:string','cidade' => 'xsd:string','pais' => 'xsd:string','latitude' => 'xsd:double','longitude' => 'xsd:double'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Altera um registro da tabela endereco.');
	$server->register('endereco.delete', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Deleta um registro da tabela endereco.');
	$server->register('endereco.select_by_id', array('id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tabela endereco por id (retorna json).');
	$server->register('endereco.select', array('condicoes' => 'xsd:string'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tabela endereco com condições definidas ou indefinidas (retorna json).');

	// Classe da tabela usuario //
	class usuario {
	    function insert($nome,$email,$senha,$cpf,$telefone) {
	    	$nome = ereg_replace("[#'/*\|`]", '',$nome);
			$email = ereg_replace("[#'/*\|`]", '',$email);
			$cpf = ereg_replace("[^0-9]", '',$cpf);
			$telefone = ereg_replace("[^0-9]", '',$telefone);
	    	if (!validar_cpf($cpf))
    			return 0;
    		$senha = sha1($senha);
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("INSERT INTO usuario VALUES(NULL,'$nome','$email','$senha','$cpf','$telefone')");
	    	$id = 0;
	    	if ($query == true)
	    		$id = $conexao->insert_id;
			$conexao->close();
			return $id;
	    }
	    function update_perfil($id,$nome,$email,$telefone) {
	    	$nome = ereg_replace("[#'/*\|`]", '',$nome);
			$email = ereg_replace("[#'/*\|`]", '',$email);
			$cpf = ereg_replace("[^0-9]", '',$cpf);
			$telefone = ereg_replace("[^0-9]", '',$telefone);
	    	if (!validar_cpf($cpf))
    			return 0;
    		$senha = sha1($senha);
	      	require_once("conectar_mysql.php");
	    	$query = $conexao->query("SELECT * FROM usuario WHERE id = $id");
	    	$retorno = false;
			if (mysqli_num_rows($query) == 1)
			{
		    	$query = $conexao->query("UPDATE usuario SET nome = '$nome', email = '$email',telefone = '$telefone' WHERE id = $id");
				$retorno = true;
			}
			$conexao->close();
	     	return $retorno;
	    }
			function update_senha($id,$senha_antiga,$senha_nova) {
    		$senha_antiga = sha1($senha_antiga);
	    	require_once("conectar_mysql.php");
	    	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM usuario WHERE id = $id");
			if (mysqli_num_rows($query) == 1)
			{
				$row = $query->fetch_assoc();
				if($row["senha"] == $senha_antiga)
				{
					$senha_nova = sha1($senha_nova);
					$query = $conexao->query("UPDATE usuario SET senha = '$senha_nova' WHERE id = $id");
					$retorno = true;
				}
			}
			$conexao->close();
	      	return $retorno;
	    }
	    function delete($id) {
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("DELETE FROM usuario_has_endereco WHERE usuario_id = $id");
	    	$query = $conexao->query("SELECT * FROM agendamento WHERE usuario_id = $id");
	    	while ($row = $query->fetch_assoc())
	    	{
	    		$agendamento_id = $row["id"];
	    		$sub_query = $conexao->query("DELETE FROM agendamento_has_tipo_lixo WHERE agendamento_id = $agendamento_id");
	    		$sub_query = $conexao->query("DELETE FROM agendamento WHERE usuario_id = $id");
	    	}
	    	$query = $conexao->query("DELETE FROM usuario WHERE id = $id");
	    	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM usuario WHERE id = $id");
	    	if (mysqli_num_rows($query) == 0)
	    		$retorno = true;
			$conexao->close();
	  		return $retorno;
	    }
	    function login($email,$senha) {
			$email = ereg_replace("[#'/*\|`]", '',$email);
			$senha = sha1($senha);
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM usuario WHERE email = '$email' AND senha = '$senha'");
			$retorno = 0;
			if (mysqli_num_rows($query) == 1)
			{
				$row = mysqli_fetch_assoc($query);
				$retorno = $row["id"];
			}
			$conexao->close();
			return $retorno;
		}
		function select($condicoes) {
			require_once("conectar_mysql.php");
			if ($condicoes != NULL)
				$condicoes = "WHERE " . $condicoes;
			$query = $conexao->query("SELECT * FROM usuario $condicoes");
			$dados = array();
			while($row = mysqli_fetch_assoc($query)) 
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
	}
	// Registro dos métodos da classe usuario //
	$server->register('usuario.insert', array('nome' => 'xsd:string','email' => 'xsd:string','senha' => 'xsd:string','cpf' => 'xsd:string', 'telefone' => 'xsd:string'), array('return' => 'xsd:integer'),$namespace,false,'rpc','encoded','Insere um registro na table usuario (retorna o id do registro inserido).');
	$server->register('usuario.update_perfil', array('id' => 'xsd:integer','nome' => 'xsd:string','email' => 'xsd:string','telefone' => 'xsd:string'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Altera um registro da tabela usuario.');
	$server->register('usuario.update_senha', array('id' => 'xsd:integer','senha_antiga' => 'xsd:string','senha_nova' => 'xsd:string'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Altera a senha de um usuario testando se a senha que ele digitou é a que está registrada.');
	$server->register('usuario.delete', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Deleta um registro da tabela usuario.');
	$server->register('usuario.login', array('email' => 'xsd:string','senha' => 'xsd:string'), array('return' => 'xsd:integer'),$namespace,false,'rpc','encoded','Pesquisa um registro da tabela usuario por email e senha (sem criptografia e retorna id).');
	$server->register('usuario.select', array('condicoes' => 'xsd:string'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tabela usuario com condições definidas ou indefinidas (retorna json).');

	// Classe da tabela usuario_has_endereco //
	class usuario_has_endereco {
		function insert($usuario_id,$endereco_id) {
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("INSERT INTO endereco VALUES(NULL,$usuario_id,$endereco_id)");
	    	$id = 0;
	    	if ($query == true)
	    		$id = $conexao->insert_id;
			$conexao->close();
	      	return $id;
	    }
	    function delete($id) {
	    	require_once("conectar_mysql.php");
    		$query = $conexao->query("DELETE FROM usuario_has_endereco WHERE id = $id");
    		$retorno = false;
	    	$query = $conexao->query("SELECT * FROM usuario_has_endereco WHERE id = $id");
	    	if (mysqli_num_rows($query) == 0)
	    		$retorno = true;
			$conexao->close();
	  		return $retorno;
	    }
		function select($condicoes) {
			require_once("conectar_mysql.php");
			if ($condicoes != NULL)
				$condicoes = "WHERE " . $condicoes;
			$query = $conexao->query("SELECT * FROM usuario_has_endereco $condicoes");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
	}
	// Registro dos métodos da classe usuario_has_endereco //
	$server->register('usuario_has_endereco.insert', array('tipo_lixo_id' => 'xsd:integer','ponto_id' => 'xsd:integer'), array('return' => 'xsd:integer'),$namespace,false,'rpc','encoded','Insere um registro na tabela usuario_has_endereco (retorna o id do registro inserido).');
	$server->register('usuario_has_endereco.delete', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Deleta um registro da tabela usuario_has_endereco.');
	$server->register('usuario_has_endereco.select', array('condicoes' => 'xsd:string'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tabela usuario_has_endereco com condições definidas ou indefinidas (retorna json).');

	// Classe da tabela empresa //
	class empresa {
	    function insert($razao_social,$nome_fantasia,$cnpj,$senha,$email) {
	    	$razao_social = ereg_replace("[#'/*\|`]", '',$razao_social);
			$nome_fantasia = ereg_replace("[#'/*\|`]", '',$nome_fantasia);
			$cnpj = ereg_replace("[^0-9]", '',$cnpj);
			$email = ereg_replace("[#'/*\|`]", '',$email);
	    	if (!validar_cnpj($cnpj))
    			return 0;
    		$senha = sha1($senha);
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("INSERT INTO empresa VALUES(NULL,'$razao_social','$nome_fantasia','$cnpj','$senha','$email')");
	    	$id = 0;
	    	if ($query == true)
	    		$id = $conexao->insert_id;
			$conexao->close();
			return $id;
	    }
	    function update_perfil($id,$razao_social,$nome_fantasia,$email) {
	    	$razao_social = ereg_replace("[#'/*\|`]", '',$razao_social);
			$nome_fantasia = ereg_replace("[#'/*\|`]", '',$nome_fantasia);
			$email = ereg_replace("[#'/*\|`]", '',$email);
	      	require_once("conectar_mysql.php");
	    	$query = $conexao->query("SELECT * FROM empresa WHERE id = $id");
	    	$retorno = false;
			if (mysqli_num_rows($query) == 1)
			{
		    	$query = $conexao->query("UPDATE empresa SET razao_social = '$razao_social',nome_fantasia = '$nome_fantasia',email = '$email' WHERE id = $id");
				$retorno = true;
			}
			$conexao->close();
	     	return $retorno;
	    }
		function update_senha($id,$senha_antiga,$senha_nova) {
	      	$senha_antiga = sha1($senha_antiga);
	    	require_once("conectar_mysql.php");
	    	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM empresa WHERE id = $id");
			if (mysqli_num_rows($query) == 1)
			{
				$row = $query->fetch_assoc();
				if($row["senha"] == $senha_antiga)
				{
					$senha_nova = sha1($senha_nova);
					$query = $conexao->query("UPDATE empresa SET senha = '$senha_nova' WHERE id = $id");
					$retorno = true;
				}
			}
			$conexao->close();
	      	return $retorno;
	    }
	    function delete($id) {
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("SELECT * FROM agendamento WHERE empresa_id = $id");
	    	while ($row = $query->fetch_assoc()){
	    		$agendamento_id = $row["id"];
	    		$sub_query = $conexao->query("DELETE FROM agendamento_has_tipo_lixo WHERE agendamento_id = $agendamento_id");
	    		$sub_query = $conexao->query("DELETE FROM agendamento WHERE empresa_id = $id");
	    	}
	    	$query = $conexao->query("SELECT * FROM ponto WHERE empresa_id = $id");
	    	while ($row = $query->fetch_assoc()){
	    		$ponto_id = $row["id"];
	    		$sub_query = $conexao->query("DELETE FROM endereco_has_ponto WHERE ponto_id = $ponto_id");
	    		$sub_query = $conexao->query("DELETE FROM tipo_lixo_has_ponto WHERE ponto_id = $ponto_id");
	    		$sub_query = $conexao->query("DELETE FROM ponto WHERE empresa_id = $id");
	    	}
	    	$query = $conexao->query("DELETE FROM empresa WHERE id = $id");
	    	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM empresa WHERE id = $id");
	    	if (mysqli_num_rows($query) == 0)
	    		$retorno = true;
			$conexao->close();
	  		return $retorno;
	    }
	    function login($email,$senha) {
			$email = ereg_replace("[#'/*\|`]", '',$email);
			$senha = sha1($senha);
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM empresa WHERE email = '$email' AND senha = '$senha'");
			$retorno = 0;
			if (mysqli_num_rows($query) == 1)
			{
				$row = mysqli_fetch_assoc($query);
				$retorno = $row["id"];
			}
			$conexao->close();
			return $retorno;
		}
		function select_by_id($id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM empresa WHERE id = $id");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
		function select($condicoes) {
			require_once("conectar_mysql.php");
			if ($condicoes != NULL)
				$condicoes = "WHERE " . $condicoes;
			$query = $conexao->query("SELECT * FROM empresa $condicoes");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
	}
	// Registro dos métodos da classe empresa //
	$server->register('empresa.insert', array('razao_social' => 'xsd:string','nome_fantasia' => 'xsd:string','cnpj' => 'xsd:string','senha' => 'xsd:string','email' => 'xsd:string'), array('return' => 'xsd:integer'),$namespace,false,'rpc','encoded','Insere um registro na table empresa (retorna o id do registro inserido).');
	$server->register('empresa.update_perfil', array('id' => 'xsd:integer','razao_social' => 'xsd:string','nome_fantasia' => 'xsd:string','email' => 'xsd:string'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Altera um registro da tabela empresa.');
	$server->register('empresa.update_senha', array('id' => 'xsd:integer','senha_antiga' => 'xsd:string','senha_nova' => 'xsd:string'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Altera a senha de uma empresa testando se a senha que ela digitou é a que está registrada.');
	$server->register('empresa.delete', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Deleta um registro da tabela empresa.');
	$server->register('empresa.login', array('email' => 'xsd:string','senha' => 'xsd:string'), array('return' => 'xsd:integer'),$namespace,false,'rpc','encoded','Pesquisa um registro da tabela empresa por login e senha (sem criptografia e retorna id).');
	$server->register('empresa.select_by_id', array('id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tabela empresa por id (retorna json).');
	$server->register('empresa.select', array('condicoes' => 'xsd:string'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tabela empresa com condições definidas ou indefinidas (retorna json).');

	// Classe da tabela ponto //
	class ponto {
	    function insert($empresa_id,$atendimento_ini,$atendimento_fim,$observacao,$telefone,$endereco_id) {
	    	$atendimento_ini = ereg_replace("[^0-9:]", '',$atendimento_ini);
			$atendimento_fim = ereg_replace("[^0-9:]", '',$atendimento_fim);
			$observacao = ereg_replace("[#'/*\|`]", '',$observacao);
			$telefone = ereg_replace("[^0-9]", '',$telefone);
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("INSERT INTO ponto VALUES(NULL,$empresa_id,'$atendimento_ini','$atendimento_fim','$observacao','$telefone',$endereco_id)");
	    	$id = 0;
	    	if ($query == true)
	    		$id = $conexao->insert_id;
			$conexao->close();
	      	return $id;
	    }
	    function update($id,$atendimento_ini,$atendimento_fim,$observacao,$telefone) {
	    	$atendimento_ini = ereg_replace("[^0-9:]", '',$atendimento_ini);
			$atendimento_fim = ereg_replace("[^0-9:]", '',$atendimento_fim);
			$observacao = ereg_replace("[#'/*\|`]", '',$observacao);
			$telefone = ereg_replace("[^0-9]", '',$telefone);
	     	require_once("conectar_mysql.php");
	     	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM ponto WHERE id = $id");
			if (mysqli_num_rows($query) == 1)
			{
		    	$query = $conexao->query("UPDATE ponto SET atendimento_ini = '$atendimento_ini',atendimento_fim = '$atendimento_fim', observacao = '$observacao', telefone = '$telefone' WHERE id = $id");
				$retorno = true;
			}
			$conexao->close();
	     	return $retorno;
	    }
	    function delete($id) {
	    	require_once("conectar_mysql.php");
    		$query = $conexao->query("DELETE FROM tipo_lixo_has_ponto WHERE ponto_id = $id");
    		$query = $conexao->query("SELECT endereco_id FROM ponto WHERE id = $id");
    		$endereco_id = mysqli_fetch_assoc($query);
    		$endereco_id = $endereco_id["endereco_id"];
    		$query = $conexao->query("DELETE FROM ponto WHERE id = $id");
    		$query = $conexao->query("DELETE FROM endereco WHERE id = $endereco_id");
    		$retorno = false;
	    	$query = $conexao->query("SELECT * FROM ponto WHERE id = $id");
	    	if (mysqli_num_rows($query) == 0)
	    		$retorno = true;
			$conexao->close();
	  		return $retorno;
	    }
		function select_by_atendimento($atendimento) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM ponto WHERE atendimento_ini < '$atendimento' AND atendimento_fim > '$atendimento'");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
		function select_by_empresa($empresa_id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM ponto WHERE empresa_id = $empresa_id");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
		function select_by_id($id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM ponto WHERE id = $id");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
		function select($condicoes) {
			require_once("conectar_mysql.php");
			if ($condicoes != NULL)
				$condicoes = "WHERE " . $condicoes;
			$query = $conexao->query("SELECT * FROM ponto $condicoes");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
	}
	// Registro dos métodos da classe ponto //
	$server->register('ponto.insert', array('empresa_id' => 'xsd:integer','atendimento_ini' => 'xsd:string','atendimento_fim' => 'xsd:string','observacao' => 'xsd:string','telefone' => 'xsd:string','endereco_id' => 'xsd:integer'), array('return' => 'xsd:integer'),$namespace,false,'rpc','encoded','Insere um registro na tabela ponto (retorna o id do registro inserido).');
	$server->register('ponto.update', array('id' => 'xsd:integer','empresa_id' => 'xsd:integer','atendimento_ini' => 'xsd:string','atendimento_fim' => 'xsd:string','observacao' => 'xsd:string','telefone' => 'xsd:string'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Altera um registro da tabela ponto.');
	$server->register('ponto.delete', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Deleta um registro da tabela ponto.');
	$server->register('ponto.select_by_atendimento', array('atendimento' => 'xsd:string'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tabela ponto por horário de atendimento (retorna json).');
	$server->register('ponto.select_by_empresa', array('empresa_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tabela ponto por empresa (retorna json).');
	$server->register('ponto.select_by_id', array('id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tabela ponto por id (retorna json).');
	$server->register('ponto.select', array('condicoes' => 'xsd:string'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tabela ponto com condições definidas ou indefinidas (retorna json).');

	// Classe da tabela tipo_lixo_has_ponto //
	class tipo_lixo_has_ponto {
		function insert($tipo_lixo_id,$ponto_id) {
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("INSERT INTO tipo_lixo_has_ponto VALUES(NULL,$tipo_lixo_id,$ponto_id)");
	    	$id = 0;
	    	if ($query == true)
	    		$id = $conexao->insert_id;
			$conexao->close();
	      	return $id;
	    }
	    function delete($id) {
	    	require_once("conectar_mysql.php");
    		$query = $conexao->query("DELETE FROM tipo_lixo_has_ponto WHERE id = $id");
    		$retorno = false;
	    	$query = $conexao->query("SELECT * FROM tipo_lixo_has_ponto WHERE id = $id");
	    	if (mysqli_num_rows($query) == 0)
	    		$retorno = true;
			$conexao->close();
	  		return $retorno;
	    }
	    function select_by_id($id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM tipo_lixo_has_ponto WHERE id = $id");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
		function select_by_ponto($ponto_id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM tipo_lixo_has_ponto WHERE ponto_id = $ponto_id");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
		function select($condicoes) {
			require_once("conectar_mysql.php");
			if ($condicoes != NULL)
				$condicoes = "WHERE " . $condicoes;
			$query = $conexao->query("SELECT * FROM tipo_lixo_has_ponto $condicoes");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
	}
	// Registro dos métodos da classe tipo_lixo_has_ponto //
	$server->register('tipo_lixo_has_ponto.insert', array('tipo_lixo_id' => 'xsd:integer','ponto_id' => 'xsd:integer'), array('return' => 'xsd:integer'),$namespace,false,'rpc','encoded','Insere um registro na tabela tipo_lixo_has_ponto (retorna o id do registro inserido).');
	$server->register('tipo_lixo_has_ponto.delete', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Deleta um registro da tabela tipo_lixo_has_ponto.');
	$server->register('tipo_lixo_has_ponto.select_by_id', array('id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa um registro da tabela tipo_lixo_has_ponto por id (retorna json).');
	$server->register('tipo_lixo_has_ponto.select_by_ponto', array('ponto_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa um registro da tabela tipo_lixo_has_ponto por empresa_id (retorna json).');
	$server->register('tipo_lixo_has_ponto.select', array('condicoes' => 'xsd:string'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros da tipo_lixo_has_ponto ponto com condições definidas ou indefinidas (retorna json).');

	// Classe da tabela notificacao //
	class notificacao {
	    function insert($usuario_id,$empresa_id,$tipo,$destino) {
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("INSERT INTO notificacao VALUES(NULL,$usuario_id,$empresa_id,$tipo,$destino)");
	    	$id = 0;
	    	if ($query == true)
	    		$id = $conexao->insert_id;
			$conexao->close();
	      	return $id;
	    }
	    function delete($id) {
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("DELETE FROM notificacao WHERE id = $id");
	    	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM notificacao WHERE id = $id");
	    	if (mysqli_num_rows($query) == 0)
	    		$retorno = true;
			$conexao->close();
	  		return $retorno;
	    }
		function select_by_usuario($usuario_id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM notificacao WHERE usuario_id = $usuario_id AND destino = TRUE");
			$dados = array();
			while($row = mysqli_fetch_assoc($query)) {
			    $dados[] = $row;
			}
			$conexao->close();
			return json_encode($dados);
		}
		function select_by_empresa($empresa_id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM notificacao WHERE empresa_id = $empresa_id AND destino = TRUE");
			$dados = array();
			while($row = mysqli_fetch_assoc($query)) {
			    $dados[] = $row;
			}
			$conexao->close();
			return json_encode($dados);
		}
	}
	// Registro dos métodos da classe notificacao //
	$server->register('notificacao.insert', array('usuario_id' => 'xsd:string','empresa_id' => 'xsd:string','tipo' => 'xsd:integer','destino' => 'xsd:boolean'), array('return' => 'xsd:integer'),$namespace,false,'rpc','encoded','Insere um registro na tabela notificacao (retorna o id do registro inserido).');
	$server->register('notificacao.delete', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Deleta um registro da tabela notificacao.');
	$server->register('notificacao.select_by_usuario', array('usuario_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa um registro da tabela notificacao por usuario (retorna json).');
	$server->register('notificacao.select_by_empresa', array('empresa_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa um registro da tabela notificacao por empresa (retorna json).');

	// Classe da tabela agendamento //
	class agendamento {
	    function insert($empresa_id,$usuario_id,$data_agendamento,$horario) {
	    	$data_agendamento = ereg_replace("[^0-9-]", '',$data_agendamento);
			$horario = ereg_replace("[^0-9:]", '',$horario);
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("INSERT INTO agendamento VALUES(NULL,$empresa_id,$usuario_id,'$data_agendamento','$horario',0,0)");
	    	$id = 0;
	    	if ($query == true)
	    		$id = $conexao->insert_id;
			$conexao->close();
	      	return $id;
	    }
	    function update($id,$data_agendamento,$horario) {
	    	$data_agendamento = ereg_replace("[^0-9-]", '',$data_agendamento);
			$horario = ereg_replace("[^0-9:]", '',$horario);
	     	require_once("conectar_mysql.php");
	     	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM agendamento WHERE id = $id");
			if (mysqli_num_rows($query) == 1)
			{
		    	$query = $conexao->query("UPDATE agendamento SET data_agendamento = '$data_agendamento',horario = '$horario' WHERE id = $id");
				$retorno = true;
			}
			$conexao->close();
	     	return $retorno;
	    }
	    function aceitar($id) {
	    	require_once("conectar_mysql.php");
	     	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM agendamento WHERE id = $id");
	    	$row = mysqli_fetch_assoc($query);
			if ((mysqli_num_rows($query) == 1) && ($row["aceito"] == 0) && ($row["realizado"] == 0))
			{
		    	$query = $conexao->query("UPDATE agendamento SET aceito = 1 WHERE id = $id");
				$retorno = true;
			}
			$conexao->close();
	     	return $retorno;
	    }
	    function recusar($id) {
	    	require_once("conectar_mysql.php");
	     	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM agendamento WHERE id = $id");
	    	$row = mysqli_fetch_assoc($query);
			if ((mysqli_num_rows($query) == 1) && ($row["aceito"] == 0) && ($row["realizado"] == 0))
			{
		    	$query = $conexao->query("DELETE FROM agendamento WHERE id = $id");
		    	$retorno = true;
			}
			$conexao->close();
	     	return $retorno;
	    }
	    function realizar($id) {
	    	require_once("conectar_mysql.php");
	     	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM agendamento WHERE id = $id");
	    	$row = mysqli_fetch_assoc($query);
			if ((mysqli_num_rows($query) == 1) && ($row["aceito"] == 1) && ($row["realizado"] == 0))
			{
		    	$query = $conexao->query("UPDATE agendamento SET realizado = 1 WHERE id = $id");
				$retorno = true;
			}
			$conexao->close();
	     	return $retorno;
	    }
	    function cancelar($id) {
	    	require_once("conectar_mysql.php");
	     	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM agendamento WHERE id = $id");
	    	$row = mysqli_fetch_assoc($query);
			if ((mysqli_num_rows($query) == 1) && ($row["aceito"] == 1) && ($row["realizado"] == 0))
			{
		    	$query = $conexao->query("DELETE FROM agendamento WHERE id = $id");
		    	$retorno = true;
			}
			$conexao->close();
	     	return $retorno;
	    }
		function select_sem_resposta_by_usuario($usuario_id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM agendamento WHERE usuario_id = $usuario_id AND aceito = 0 AND realizado = 0");
			$dados = array();
			while($row = mysqli_fetch_assoc($query)) {
			    $dados[] = $row;
			}
			$conexao->close();
			return json_encode($dados);
		}
		function select_em_espera_by_usuario($usuario_id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM agendamento WHERE usuario_id = $usuario_id AND aceito = 1 AND realizado = 0 ORDER BY data_agendamento, horario DESC");
			$dados = array();
			while($row = mysqli_fetch_assoc($query)) {
			    $dados[] = $row;
			}
			$conexao->close();
			return json_encode($dados);
		}
		function select_realizados_by_usuario($usuario_id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM agendamento WHERE usuario_id = $usuario_id AND aceito = 1 AND realizado = 1 ORDER BY data_agendamento, horario DESC");
			$dados = array();
			while($row = mysqli_fetch_assoc($query)) {
			    $dados[] = $row;
			}
			$conexao->close();
			return json_encode($dados);
		}
		function select_atrasados_by_usuario($usuario_id) {
			$data = date("Y-m-d");
			$horario = date("H:i:s.u");
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM agendamento WHERE usuario_id = $usuario_id AND aceito = 1 AND realizado = 0 AND data_agendamento < '$data' AND horario < '$horario' ORDER BY data_agendamento, horario DESC");
			$dados = array();
			while($row = mysqli_fetch_assoc($query)) {
			    $dados[] = $row;
			}
			$conexao->close();
			return json_encode($dados);
		}
		function select_sem_resposta_by_empresa($empresa_id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM agendamento WHERE empresa_id = $empresa_id AND aceito = 0 AND realizado = 0");
			$dados = array();
			while($row = mysqli_fetch_assoc($query)) {
			    $dados[] = $row;
			}
			$conexao->close();
			return json_encode($dados);
		}
		function select_em_espera_by_empresa($empresa_id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM agendamento WHERE empresa_id = $empresa_id AND aceito = 1 AND realizado = 0 ORDER BY data_agendamento, horario DESC");
			$dados = array();
			while($row = mysqli_fetch_assoc($query)) {
			    $dados[] = $row;
			}
			$conexao->close();
			return json_encode($dados);
		}
		function select_realizados_by_empresa($empresa_id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM agendamento WHERE empresa_id = $empresa_id AND aceito = 1 AND realizado = 1 ORDER BY data_agendamento, horario DESC");
			$dados = array();
			while($row = mysqli_fetch_assoc($query)) {
			    $dados[] = $row;
			}
			$conexao->close();
			return json_encode($dados);
		}
		function select_atrasados_by_empresa($empresa_id) {
			$data = date("Y-m-d");
			$horario = date("H:i:s.u");
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM agendamento WHERE empresa_id = $empresa_id AND aceito = 1 AND realizado = 0  AND data_agendamento < '$data' AND horario < '$horario' ORDER BY data_agendamento, horario DESC");
			$dados = array();
			while($row = mysqli_fetch_assoc($query)) {
			    $dados[] = $row;
			}
			$conexao->close();
			return json_encode($dados);
		}
		function select($condicoes) {
			require_once("conectar_mysql.php");
			if ($condicoes != NULL)
				$condicoes = "WHERE " . $condicoes;
			$query = $conexao->query("SELECT * FROM agendamento $condicoes");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
	}
	// Registro dos métodos da classe agendamento //
	$server->register('agendamento.insert', array('empresa_id' => 'xsd:integer','usuario_id' => 'xsd:integer','data_agendamento' => 'xsd:string','horario' => 'xsd:string','aceito' => 'xsd:string','realizado' => 'xsd:string'), array('return' => 'xsd:integer'),$namespace,false,'rpc','encoded','Insere um registro na tabela agendamento (retorna o id do registro inserido).');
	$server->register('agendamento.update', array('id' => 'xsd:integer','data_agendamento' => 'xsd:string','horario' => 'xsd:string'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Altera um registro na tabela agendamento (retorna o id do registro inserido).');
	$server->register('agendamento.aceitar', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Aceita um agendamento.');
	$server->register('agendamento.recusar', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Recusa um agendamento.');
	$server->register('agendamento.realizar', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Realiza um agendamento.');
	$server->register('agendamento.cancelar', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Aceita um agendamento.');
	$server->register('agendamento.select_sem_resposta_by_usuario', array('usuario_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa agendamentos sem reposta da tabela agendamento por usuario (retorna json).');
	$server->register('agendamento.select_em_espera_by_usuario', array('usuario_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa agendamentos pendentes da tabela agendamento por usuario (retorna json).');
	$server->register('agendamento.select_realizados_by_usuario', array('usuario_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa agendamentos sem reposta da tabela agendamento por usuario (retorna json).');
	$server->register('agendamento.select_atrasados_by_usuario', array('usuario_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa agendamentos atrasados da tabela agendamento por usuario (retorna json).');
	$server->register('agendamento.select_sem_resposta_by_empresa', array('empresa_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa agendamentos sem reposta da tabela agendamento por usuario (retorna json).');
	$server->register('agendamento.select_em_espera_by_empresa', array('empresa_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa agendamentos pendentes da tabela agendamento por usuario (retorna json).');
	$server->register('agendamento.select_realizados_by_empresa', array('empresa_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa agendamentos sem reposta da tabela agendamento por usuario (retorna json).');
	$server->register('agendamento.select_atrasados_by_empresa', array('usuario_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa agendamentos atrasados da tabela agendamento por empresa (retorna json).');
	$server->register('agendamento.select', array('condicoes' => 'xsd:string'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros com condições definidas ou indefinidas (retorna json).');

	// Classe da tabela agendamento_has_tipo_lixo //
	class agendamento_has_tipo_lixo {
	    function insert($agendamento_id,$tipo_lixo_id,$quantidade) {
	    	$quantidade = ereg_replace("[^0-9.]", '',$quantidade);
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("INSERT INTO agendamento_has_tipo_lixo VALUES(NULL,$agendamento_id,$tipo_lixo_id,$quantidade)");
	    	$id = 0;
	    	if ($query == true)
	    		$id = $conexao->insert_id;
			$conexao->close();
	      	return $id;
	    }
	    function update($id,$quantidade) {
	    	$quantidade = ereg_replace("[^0-9.]", '',$quantidade);
	     	require_once("conectar_mysql.php");
	     	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM agendamento_has_tipo_lixo WHERE id = $id");
			if (mysqli_num_rows($query) == 1)
			{
		    	$query = $conexao->query("UPDATE agendamento_has_tipo_lixo SET quantidade = $quantidade WHERE id = $id");
				$retorno = true;
			}
			$conexao->close();
	     	return $retorno;
	    }
	    function delete($id) {
	    	require_once("conectar_mysql.php");
	    	$query = $conexao->query("DELETE FROM agendamento_has_tipo_lixo WHERE id = $id");
	    	$retorno = false;
	    	$query = $conexao->query("SELECT * FROM agendamento_has_tipo_lixo WHERE id = $id");
	    	if (mysqli_num_rows($query) == 0)
	    		$retorno = true;
			$conexao->close();
	  		return $retorno;
	    }
		function select_by_agendamento($agendamento_id) {
			require_once("conectar_mysql.php");
			$query = $conexao->query("SELECT * FROM agendamento_has_tipo_lixo WHERE agendamento_id = $agendamento_id");
			$dados = array();
			while($row = mysqli_fetch_assoc($query)) {
			    $dados[] = $row;
			}
			$conexao->close();
			return json_encode($dados);
		}
		function select($condicoes) {
			require_once("conectar_mysql.php");
			if ($condicoes != NULL)
				$condicoes = "WHERE " . $condicoes;
			$query = $conexao->query("SELECT * FROM agendamento_has_tipo_lixo $condicoes");
			$dados = array();
			while($row = mysqli_fetch_assoc($query))
			    $dados[] = $row;
			$conexao->close();
			return json_encode($dados);
		}
	}
	// Registro dos métodos da classe agendamento_has_tipo_lixo //
	$server->register('agendamento_has_tipo_lixo.insert', array('agendamento_id' => 'xsd:integer','usuario_id' => 'xsd:integer','quantidade' => 'xsd:double'), array('return' => 'xsd:integer'),$namespace,false,'rpc','encoded','Insere um registro na tabela agendamento_has_tipo_lixo (retorna o id do registro inserido).');
	$server->register('agendamento_has_tipo_lixo.update', array('quantidade' => 'xsd:double'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Insere um registro na tabela agendamento_has_tipo_lixo (retorna o id do registro inserido).');
	$server->register('agendamento_has_tipo_lixo.delete', array('id' => 'xsd:integer'), array('return' => 'xsd:boolean'),$namespace,false,'rpc','encoded','Deleta um registro da tabela agendamento_has_tipo_lixo.');
	$server->register('agendamento_has_tipo_lixo.select_by_agendamento', array('agendamento_id' => 'xsd:integer'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa um registro da tabela agendamento_has_tipo_lixo por agendamento (retorna json).');
	$server->register('agendamento_has_tipo_lixo.select', array('condicoes' => 'xsd:string'), array('return' => 'xsd:string'),$namespace,false,'rpc','encoded','Pesquisa registros com condições definidas ou indefinidas (retorna json).');

	$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';
	$server->service($HTTP_RAW_POST_DATA);
?>