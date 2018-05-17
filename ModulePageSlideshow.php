<?php

/**
 * pageimage Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2018 Comolo GmbH
 * @author     Hendrik Obermayer <github.com/henobi>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/comolo/contao-pageimage
 */


class ModulePageSlideshow extends \Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_page_slideshow';


    public function generate()
    {
        if (TL_MODE == 'BE')
        {
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### PAGE SLIDESHOW ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=modules&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }


    protected function compile()
    {
        if ($this->defineRoot) {
            $objPage = \PageModel::findByPk($this->rootPage);
        } else {
            global $objPage;
        }

        if (null === $objPage) {
            return;
        }

        $arrImages = PageSlideshow::getMultiple($objPage);

        if (null === $arrImages || !count($arrImages)) {
            return;
        }

        $arrTemplateImages = [];

        foreach ($arrImages as $arrImage) {
            $image = [];
            $arrSize = deserialize($this->imgSize);
            $image['src'] = $this->getImage($arrImage['path'], $arrSize[0], $arrSize[1], $arrSize[2]);


            $picture = \Picture::create($arrImage['path'], $arrSize)->getTemplateData();
            $picture['alt'] = specialchars($arrImage['alt']);
            $image['picture'] = $picture;

            if (($imgSize = @getimagesize(TL_ROOT . '/' . rawurldecode($arrImage['src']))) !== false) {
                $image['size'] = ' ' . $imgSize[3];
            }

            // Add to array
            $arrTemplateImages[] = $image;
        }

        $this->Template->images = $arrTemplateImages;

        // Add page information to template
        global $objPage;
        $this->Template->currentPage = $objPage->row();
    }
}
