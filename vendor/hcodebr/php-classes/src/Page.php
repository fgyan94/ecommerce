<?php 

namespace Hcode;

use Rain\Tpl;

class Page {
	private $tpl;
	// OPÇÕES PADRÃO, CASO NENHUMA VARIÁVEL SEJA PASSADA NO CONSTRUTOR
	private $opts = [];
	private $defaults = [
			"header"=>true,
			"footer"=>true,
			"data"=>[]
	];
	
	// AS VARIÁVEIS VIRÃO DE ACORDO COM A ROTA
	public function __construct($opts = array(), $tpl_dir = "/views/") {
		
		// MERGE DAS OPÇÕES DA PÁGINA
		// O ARRAY PASSADO NO ÚLTIMO PARÂMETRO SOBRESCREVE OS DEMAIS EM CASO DE CONFLITO DE DADOS
		$this->opts = array_merge($this->defaults, $opts);		
		
		$config = array(
				// DIRETÓRIO ONDE AS PAGES HTML ESTARÃO LOCALIZADAS
				// OBS: INDO ATÉ O DIRTÓRIO ROOT -> $_SERVER['DOCUMENT_ROOT']
				"tpl_dir"       => $_SERVER['DOCUMENT_ROOT'].$tpl_dir,
				// CACHE DAS PÁGINAS
				"cache_dir"     => $_SERVER['DOCUMENT_ROOT']."/views-cache/",
				"debug"         => false // set to false to improve the speed
		);
		
		Tpl::configure( $config );
		
		$this->tpl = new Tpl();
		
		// PASSANDO OS DADOS PRAS VARIÁVEIS DO TEMPLATE		
		$this->setData($this->opts['data']);
		
		//  RENDERIZAR O HEADER DA PÁGINA A PARTIR DO TEMPLATE E OS DADOS ENVIADOS PARA ELE
		if($this->opts['header']) $this->tpl->draw("header");
		
	}
	
	// RENDERIZANDO CONTEUDO DA PÁGINA
	public function setTPL($tplName, $data = array(), $returnHTML = false) {
		$this->setData($data);
		return $this->tpl->draw($tplName, $returnHTML);
	}
	
	public function __destruct() {
		// AO LIBERAR A MEMÓRIA DO PHP, RENDERIZAR O RODAPÉ DA PÁGINA
		if($this->opts['footer']) $this->tpl->draw("footer");
	}
	
	private function setData($data = array()) {
		foreach($data as $key => $value) {
			$this->tpl->assign($key, $value);
		}
	}
}

?>