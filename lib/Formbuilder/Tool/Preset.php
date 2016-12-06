<?php

namespace Formbuilder\Tool;

use Pimcore\Tool as PimcoreTool;
use Pimcore\Model\Site;
use Formbuilder\Model\Configuration;

class Preset {

    /**
     * @param      $preset
     * @param bool $language
     *
     * @return array
     */
    public static function getPresetConfig( $preset, $language = FALSE )
    {
        $formPresets = Configuration::get('form.area.presets');

        $dat = [ 'mail' => FALSE, 'mailCopy' => FALSE ];

        if( empty( $formPresets ) )
        {
            return $dat;
        }

        foreach( $formPresets as $presetName => $presetConfig )
        {
            if( $presetName === $preset)
            {
                $rootPath = '/';

                $siteRequest = isset( $presetConfig['site'] ) && !empty( $presetConfig['site'] ) ? (array) $presetConfig['site'] : FALSE;

                if( $siteRequest !== FALSE && Site::isSiteRequest())
                {
                    $site = Site::getCurrentSite();

                    if( !in_array( $site->getMainDomain(), $siteRequest ) )
                    {
                        continue;
                    }

                    $rootPath = rtrim( $site->getRootPath(), '/') . '/';
                }

                if( isset( $presetConfig['mail'] ) && !empty( $presetConfig['mail'] ) )
                {
                    if( is_string( $presetConfig['mail'] ) )
                    {
                        $dat['mail'] = $rootPath . ltrim( $presetConfig['mail'], '/' );
                    }
                    else if( is_array( $presetConfig['mail'] ))
                    {
                        foreach( $presetConfig['mail'] as $languageKey => $mail )
                        {
                            if( $languageKey === $language )
                            {
                                $dat['mail'] = $rootPath . ltrim( $mail, '/' );
                                break;
                            }
                        }
                    }
                }

                if( isset( $presetConfig['mailCopy'] ) && !empty( $presetConfig['mailCopy'] ) )
                {
                    if( is_string( $presetConfig['mailCopy'] ) )
                    {
                        $dat['mailCopy'] = $rootPath . ltrim( $presetConfig['mailCopy'], '/' );
                    }
                    else if( is_array( $presetConfig['mailCopy'] ))
                    {
                        foreach( $presetConfig['mailCopy'] as $languageKey => $mail )
                        {
                            if( $languageKey === $language )
                            {
                                $dat['mailCopy'] = $rootPath . ltrim( $mail, '/' );
                                break;
                            }
                        }
                    }
                }

                break;
            }
        }

        return $dat;

    }

    public static function getAvailablePresets()
    {
        $formPresets = Configuration::get('form.area.presets');

        $dat = [];

        if( empty( $formPresets ) )
        {
            return $dat;
        }

        foreach( $formPresets as $presetName => $presetConfig )
        {
            //check for site restriction
            if( PimcoreTool::isFrontentRequestByAdmin() && isset( $presetConfig['site'] ) && !empty( $presetConfig['site'] ) )
            {
                $currentSite = self::getCurrentSiteInAdminMode();

                if( $currentSite !== NULL )
                {
                    $allowedSites = (array) $presetConfig['site'];

                    if( !in_array( $currentSite->getMainDomain(), $allowedSites ) )
                    {
                        continue;
                    }
                }
            }

            $dat[ $presetName ] = $presetConfig;
        }

        return $dat;

    }

    public static function getDataForPreview( $presetName, $presetConfig, $language )
    {
        $previewData = [ 'presetName' => $presetName, 'description' => '', 'fields' => [] ];

        $rootPath = '/';

        if( PimcoreTool::isFrontentRequestByAdmin() && isset( $presetConfig['site'] ) && !empty( $presetConfig['site'] ) )
        {
            $currentSite = self::getCurrentSiteInAdminMode();
            $rootPath = rtrim($currentSite->getRootPath(), '/') . '/';
        }

        if( isset( $presetConfig['mail'] ) && !empty( $presetConfig['mail'] ) )
        {
            $line = [ 'label' => 'Mail', 'value' => NULL ];

            if( is_string( $presetConfig['mail'] ) )
            {
                $line['value'] = ltrim( $presetConfig['mail'], '/' );
            }
            else if( is_array( $presetConfig['mail'] ))
            {
                foreach( $presetConfig['mail'] as $languageKey => $mail )
                {
                    if( $languageKey === $language )
                    {
                        $line['value'] = $rootPath . ltrim( $mail, '/' );
                        break;
                    }
                }
            }

            $previewData['fields'][] = $line;
        }

        if( isset( $presetConfig['mailCopy'] ) && !empty( $presetConfig['mailCopy'] ) )
        {
            $line = [ 'label' => 'Mail Copy', 'value' => NULL ];

            if( is_string( $presetConfig['mailCopy'] ) )
            {
                $line['value'] = $rootPath . ltrim( $presetConfig['mailCopy'], '/' );
            }
            else if( is_array( $presetConfig['mailCopy'] ))
            {
                foreach( $presetConfig['mailCopy'] as $languageKey => $mail )
                {
                    if( $languageKey === $language )
                    {
                        $line['value'] = $rootPath . ltrim( $mail, '/' );
                        break;
                    }
                }
            }

            $previewData['fields'][] = $line;
        }

        if( isset( $presetConfig['adminDescription'] ) )
        {
            $previewData['description'] = strip_tags($presetConfig['adminDescription'], '<br><strong><em><p><span>');
        }

        return $previewData;
    }

    /**
     * Get Site Id in EditMode if SiteRequest is available
     * @return null|\Pimcore\Model\Site
     */
    private static function getCurrentSiteInAdminMode()
    {
        $front = \Zend_Controller_Front::getInstance();
        $originDocument = $front->getRequest()->getParam('document');

        $currentSite = NULL;

        if ($originDocument)
        {
            $site = PimcoreTool\Frontend::getSiteForDocument($originDocument);

            if ($site)
            {
                $siteId = $site->getId();

                if( $siteId !== NULL )
                {
                    $currentSite = $site;
                }
            }
        }

        return $currentSite;
    }
}