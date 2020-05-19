<?php 

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension{
    public function getFilters()
    {
        return [
            new TwigFilter('extractText',[$this, 'extractTextFromBD'])
        ];
    }

    public function extractTextFromBD($content, $length = 10){
        
        $content = $content;
        $content = substr($content, 0 , $length);
        $content = substr($content, 0, strrpos($content, ' ')).' ...';

        return str_replace('<p>','', $content);
    }
}