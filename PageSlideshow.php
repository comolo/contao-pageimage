<?php

/**
 * pageimage Extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2009-2014, terminal42 gmbh, 2018 Comolo GmbH
 * @author     terminal42 gmbh <info@terminal42.ch>, Hendrik Obermayer <github.com/henobi>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       http://github.com/comolo/contao-pageimage
 */


class PageSlideshow extends Frontend
{

    /**
     * Images
     * @var array
     */
    protected static $arrImages;

    /**
     * Get one image by offset
     * @param   \PageModel
     * @param   int
     * @param   bool
     * @return  array|null
     */
    public static function getOne(\PageModel $objPage, $intIndex=0, $blnInherit=true)
    {
        $arrImages = static::findForPage($objPage, $blnInherit);

        if ($arrImages === null) {
            return null;
        }

        // Random image
        if ($intIndex < 0) {
            $intIndex = rand(0, count($arrImages) - 1);
        }

         if (!isset($arrImages[$intIndex])) {
            return null;
        }

        return $arrImages[$intIndex];
    }

    /**
     * Get multiple images by offset and length
     * @param   int
     * @param   int|null
     * @param   bool
     * @return  array
     */
    public static function getMultiple(\PageModel $objPage, $blnInherit=true)
    {
        $arrImages = static::findForPage($objPage, $blnInherit);

        if (null === $arrImages) {
            return [];
        }

        return $arrImages;
    }


    /**
     * Find images for given page
     * @param   \PageModel
     * @return  array
     */
    protected static function findForPage(\PageModel $objPage, $blnInherit=true)
    {
        if (!isset(static::$arrImages[$objPage->id])) {

            static::$arrImages[$objPage->id] = false;

            $arrImages = static::parsePage($objPage);

            if (!empty($arrImages)) {
                static::$arrImages[$objPage->id] = array(
                    'images'    => $arrImages,
                    'inherited' => false
                );
            }

            // Walk the trail
            else {
                $objPage->loadDetails();
                $objTrails = \PageModel::findMultipleByIds(array_reverse($objPage->trail));

                if (null !== $objTrails) {
                    foreach ($objTrails as $objTrail) {
                        $arrImages = static::parsePage($objTrail);

                        if (!empty($arrImages)) {
                            static::$arrImages[$objPage->id] = array(
                                'images'    => $arrImages,
                                'inherited' => true
                            );

                            break;
                        }
                    }
                }
            }
        }

        if (static::$arrImages[$objPage->id] === false || (!$blnInherit && static::$arrImages[$objPage->id]['inherited'])) {
            return null;
        }

        return static::$arrImages[$objPage->id]['images'];
    }

    /**
     * Parse the given page and return the image information
     * @param    \PageModel
     * @return   array
     */
    protected static function parsePage(\PageModel $objPage)
    {
        if ($objPage->pageSlideshow == '') {
            return [];
        }

        $arrImages = array();
        $objImages = \FilesModel::findMultipleByIds(deserialize($objPage->pageSlideshow, true));

        if (null !== $objImages) {
            while ($objImages->next()) {

                $objFile = new \File($objImages->path, true);

                if (!$objFile->isGdImage) {
                    continue;
                }

                $arrImage = $objImages->row();
                $arrMeta = static::getMetaData($objImages->meta, $objPage->language);

                // Use the file name as title if none is given
                if ($arrMeta['title'] == '') {
                    $arrMeta['title'] = specialchars(str_replace('_', ' ', preg_replace('/^[0-9]+_/', '', $objFile->filename)));
                }

                $arrImage['imageUrl'] = $arrMeta['link'];
                $arrImages[] = $arrImage;
            }

            $arrOrder = deserialize($objPage->pageSlideshowOrder);

            if (!empty($arrOrder) && is_array($arrOrder))
            {
            	// Remove all values
            	$arrOrder = array_map(function(){}, array_flip($arrOrder));

            	// Move the matching elements to their position in $arrOrder
            	foreach ($arrImages as $k=>$v)
            	{
            		if (array_key_exists($v['uuid'], $arrOrder))
            		{
            			$arrOrder[$v['uuid']] = $v;
            			unset($arrImages[$k]);
            		}
            	}

            	// Append the left-over images at the end
            	if (!empty($arrImages))
            	{
            		$arrOrder = array_merge($arrOrder, array_values($arrImages));
            	}

            	// Remove empty (unreplaced) entries
            	$arrImages = array_values(array_filter($arrOrder));
            	unset($arrOrder);
            }
        }

        return $arrImages;
    }
}
