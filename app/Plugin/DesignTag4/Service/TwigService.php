<?php

/*
 * This file is part of DesignTag4
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\DesignTag4\Service;

use Twig\Environment;

class TwigService
{
    /**
     * @var string
     */
    private $source;

    /**
     * @var array
     */
    private $lines;

    /**
     * @var Environment
     */
    protected $twig;

    public function __construct(
        Environment $twig
    ) {
        $this->twig = $twig;
    }

    public function setSource($source)
    {
        $this->source = $source;
        $this->lines = explode(PHP_EOL, $this->convEOL($this->source));

        return $this;
    }

    public function replace($search, $replace)
    {
        $replace = $this->convEOL($replace);
        $source = str_replace($search, $replace, $this->source);
        $this->source = $source;
        $this->lines = explode(PHP_EOL, $this->convEOL($this->source));
    }

    public function preg_replace($search, $replace)
    {
        $replace = $this->convEOL($replace);
        $source = preg_replace($search, $replace, $this->source);
        $this->source = $source;
        $this->lines = explode(PHP_EOL, $this->convEOL($this->source));
    }

    public function insert($search, $insert, $offset = 1)
    {
        $searchIndex = 0;

        $index = $this->getLineNumber($search, $searchIndex);

        $insertIndex = $index + $offset;

        $max = count($this->lines);
        for ($i = $max; $i > $insertIndex; $i--) {
            $this->lines[$i] = $this->lines[$i - 1];
        }

        $this->lines[$insertIndex] = $insert;
        $this->source = implode(PHP_EOL, $this->lines);
        $this->lines = explode(PHP_EOL, $this->convEOL($this->source));
    }

    public function insertAtLineNumber($line_number, $insert)
    {
        $max = count($this->lines);
        for ($i = $max; $i > $line_number; $i--) {
            $this->lines[$i] = $this->lines[$i - 1];
        }

        $this->lines[$line_number] = $insert;
        $this->source = implode(PHP_EOL, $this->lines);
        $this->lines = explode(PHP_EOL, $this->convEOL($this->source));
    }

    public function getTwigFile($path)
    {
        $twigFile = $this->twig->getLoader()->getSourceContext($path)->getCode();

        return $twigFile;
    }

    public function getSource()
    {
        return $this->source;
    }

    private function getLine($search, $searchIndex = 0)
    {
        $index = $this->getLineNumber($search, $searchIndex);

        if ($index < 0) {
            return '';
        }

        return $this->lines[$index];
    }

    private function getLineNumber($search, $searchIndex = 0)
    {
        $cnt = 0;
        foreach ($this->lines as $key => $val) {
            if (strpos($val, $search) !== false) {
                if ($cnt == $searchIndex) {
                    return $key;
                }
                $cnt++;
            }
        }

        return -1;
    }

    public function search($text, $regex = false, $start_line = 0)
    {
        for ($i = max(0, $start_line); $i < count($this->lines); $i++) {
            $val = $this->lines[$i];

            if (!$regex && strpos($val, $text) !== false) {
                return $i;
            } elseif ($regex && preg_match($text, $val)) {
                return $i;
            }
        }

        return -1;
    }

    public function search_set($text1, $text2, $regex1 = false, $regex2 = false, $start_line = 0)
    {
        $line_number = $this->search($text1, $regex1, $start_line);
        if (0 <= $line_number) {
            $line_number2 = $this->search($text2, $regex2, $line_number);
            if (0 <= $line_number2) {
                return [$line_number, $line_number2];
            }
        }

        return false;
    }

    private function convEOL($string, $to = PHP_EOL)
    {
        return preg_replace("/\r\n|\r|\n/", $to, $string);
    }

    public function insertBefore($insert, $search, $is_regex = false, $separator = '')
    {
        if ($is_regex) {
            $this->preg_replace($search, $insert.$separator.'$0');
        } else {
            $this->replace($search, $insert.$separator.$search);
        }
    }

    public function prependToBlock($block, $insert)
    {
        $insert = $this->convEOL($insert);
        if (preg_match('@(\{\%\s*block\s+'.$block.'\s*\%\})@', $this->source, $hits)) {
            $source = str_replace($hits[1], $hits[1]."\n".$insert, $this->source);
            $this->source = $source;
            $this->lines = explode(PHP_EOL, $this->convEOL($this->source));
        } else {
            $this->source .= "\n{% block ".$block." %}\n".$insert."\n{% endblock %}";
            $this->lines = explode(PHP_EOL, $this->convEOL($this->source));
        }
    }

    public function appendToBlock($block, $insert)
    {
        $insert = $this->convEOL($insert);
        $line = $this->search('@\{\%\s*block\s+'.$block.'\s*\%\}@u', true);
        if (0 <= $line) {
            $line = $this->search('@\{\%\s*endblock.*\}@u', true, $line);
            if (0 <= $line) {
                $line_text = $this->lines[$line];
                $this->lines[$line] = preg_replace('@\{\%\s*endblock.*\}@u', $insert."\n$0", $line_text, 1);
                $this->source = implode(PHP_EOL, $this->lines);
                $this->lines = explode(PHP_EOL, $this->convEOL($this->source));
            } else {
                // endblockが見つからない
            }
        } else {
            $this->source .= "\n{% block ".$block." %}\n".$insert."\n{% endblock %}";
            $this->lines = explode(PHP_EOL, $this->convEOL($this->source));
        }
    }
}
