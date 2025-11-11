<?php

class MustacheRenderer
{
    private $mustache;
    private $viewsFolder;
    private $data = [];

    public function __construct($partialsPathLoader){

        $this->mustache = new Mustache_Engine(
            array(
                'partials_loader' => new Mustache_Loader_FilesystemLoader( $partialsPathLoader )
            ));
        $this->viewsFolder = $partialsPathLoader;
    }

    public function addKey($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function render($contentFile , $contentData = array() ){
        $data = array_merge($this->data, $contentData);

        echo  $this->generateHtml(  $this->viewsFolder . '/' . $contentFile . "Vista.mustache" , $data);
    }

    public function generateHtml($contentFile, $data = array()) {
        $contentAsString = file_get_contents(  $this->viewsFolder .'/header.mustache');
        $contentAsString .= file_get_contents( $contentFile );
        $contentAsString .= file_get_contents($this->viewsFolder . '/footer.mustache');
        return $this->mustache->render($contentAsString, $data);
    }
}