<?php

/*
 * Textpattern Content Management System
 * https://textpattern.com/
 *
 * Copyright (C) 2018 The Textpattern Development Team
 *
 * This file is part of Textpattern.
 *
 * Textpattern is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * Textpattern is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Textpattern. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * Help subsystem.
 *
 * @since   4.7.0
 * @package Admin\Help
 */

namespace Textpattern\Module\Help;

class HelpAdmin
{
    private static $available_steps = array(
        'pophelp'   => false,
        'dashboard' => false,
    );

    private static $textile;
    protected static $pophelp_xml;

    /**
     * Constructor.
     *
     */

    public static function init()
    {
        global $step;
        require_privs('help');

        if ($step && bouncer($step, self::$available_steps)) {
            self::$step();
        } else {
            self::dashboard();
        }
    }


    /**
     * Load pophelp.xml
     *
     * @param string    $lang
     */

    private static function pophelp_load($lang)
    {
        $file = txpath."/lang/{$lang}_pophelp.xml";

        if (!file_exists($file)) {
            return false;
        }

        if (empty(self::$pophelp_xml)) {
            self::$pophelp_xml = simplexml_load_file($file, "SimpleXMLElement", LIBXML_NOCDATA);
        }

        return self::$pophelp_xml;
    }

    /**
     * Get pophelp group keys
     *
     * @param string    $group
     */

    public static function pophelp_keys($group)
    {
        $xml = self::pophelp_load(TEXTPATTERN_DEFAULT_LANG);
        $help = $xml ? $xml->xpath("//group[@id='{$group}']/item") : array();

        $keys = array();

        foreach ($help as $item) {
            if ($item->attributes()->id) {
                $keys[] = (string)$item->attributes()->id;
            }
        }

        return $keys;
    }

    /**
     * pophelp.
     */

    public static function pophelp($string = '')
    {
        global $app_mode;

        $item = empty($string) ? gps('item') : $string;
        if (empty($item) || preg_match('/[^\w]/i', $item)) {
            exit;
        }

        $lang_ui = get_pref('language_ui', LANG);

        if (!$xml = self::pophelp_load($lang_ui)) {
            $lang_ui = TEXTPATTERN_DEFAULT_LANG;
            $xml = self::pophelp_load($lang_ui);
        }

        $x = $xml->xpath("//item[@id='{$item}']");
        if (!$x && $lang_ui != TEXTPATTERN_DEFAULT_LANG) {
            $xml = self::pophelp_load(TEXTPATTERN_DEFAULT_LANG);
            $x = $xml->xpath("//item[@id='{$item}']");
        }

        $title = '';
        if ($x) {
            $pophelp = trim($x[0]);
            $title = txpspecialchars($x[0]->attributes()->title);
            $format = $x[0]->attributes()->format;
            if ($format == 'textile') {
                $textile = new \Netcarver\Textile\Parser();
                $out = $textile->textileThis($pophelp).n;
            } else {
                $out = $pophelp.n;
            }
        } else {
            $out = gTxt('pophelp_missing', array('{item}' => $item));
        }

        $out = tag($out, 'div', array(
            'id'  => 'pophelp-event',
            'dir' => 'auto',
        ));

        if ($app_mode == 'async') {
            pagetop('');
            exit($out);
        }

        return $out;
    }

    /**
     * Stub, waiting Txp 4.8
     *
     */

    public static function dashboard()
    {
        pagetop(gTxt('tab_help'));
    }
}
