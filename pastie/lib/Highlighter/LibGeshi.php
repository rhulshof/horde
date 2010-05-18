<?php
/**
 * 
 * @package Pastie
 */
require_once 'geshi.php';

// This file is named LibGeshi to avoid recursive includes/requires.
class Pastie_Highlighter_LibGeshi extends Pastie_Highlighter {
    public static function output($text, $syntax = 'none') {
        if ($syntax == 'none') {
            return '<pre>' . $text . '</pre>';
        } else {
            // Since we may be coming from another syntax highlighter,
            // we'll try downcasing the syntax name and hope we get lucky.
            $syntax = strtolower($syntax);
            $geshi = new GeSHi($text, $syntax);
        }
        $geshi->set_header_type(GESHI_HEADER_PRE_TABLE);
        $geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
        return $geshi->parse_code();
    }

    public static function getSyntaxes()
    {
        return array(
            "4cs",
            "abap",
            "actionscript",
            "actionscript3",
            "ada",
            "apache",
            "applescript",
            "apt_sources",
            "asm",
            "asp",
            "autohotkey",
            "autoit",
            "avisynth",
            "awk",
            "bash",
            "basic4gl",
            "bf",
            "bibtex",
            "blitzbasic",
            "bnf",
            "boo",
            "c",
            "c_mac",
            "caddcl",
            "cadlisp",
            "cfdg",
            "cfm",
            "cil",
            "clojure",
            "cmake",
            "cobol",
            "cpp-qt",
            "cpp",
            "csharp",
            "css",
            "cuesheet",
            "d",
            "dcs",
            "delphi",
            "diff",
            "div",
            "dos",
            "dot",
            "eiffel",
            "email",
            "erlang",
            "fo",
            "fortran",
            "freebasic",
            "fsharp",
            "gambas",
            "gdb",
            "genero",
            "gettext",
            "glsl",
            "gml",
            "gnuplot",
            "groovy",
            "haskell",
            "hq9plus",
            "html4strict",
            "idl",
            "ini",
            "inno",
            "intercal",
            "io",
            "java",
            "java5",
            "javascript",
            "jquery",
            "kixtart",
            "klonec",
            "klonecpp",
            "latex",
            "lisp",
            "locobasic",
            "logtalk",
            "lolcode",
            "lotusformulas",
            "lotusscript",
            "lscript",
            "lsl2",
            "lua",
            "m68k",
            "make",
            "mapbasic",
            "matlab",
            "mirc",
            "mmix",
            "modula3",
            "mpasm",
            "mxml",
            "mysql",
            "newlisp",
            "nsis",
            "oberon2",
            "objc",
            "ocaml-brief",
            "ocaml",
            "oobas",
            "oracle11",
            "oracle8",
            "pascal",
            "per",
            "perl",
            "perl6",
            "php-brief",
            "php",
            "pic16",
            "pike",
            "pixelbender",
            "plsql",
            "povray",
            "powerbuilder",
            "powershell",
            "progress",
            "prolog",
            "properties",
            "providex",
            "purebasic",
            "python",
            "qbasic",
            "rails",
            "rebol",
            "reg",
            "robots",
            "rsplus",
            "ruby",
            "sas",
            "scala",
            "scheme",
            "scilab",
            "sdlbasic",
            "smalltalk",
            "smarty",
            "sql",
            "systemverilog",
            "tcl",
            "teraterm",
            "text",
            "thinbasic",
            "tsql",
            "typoscript",
            "vb",
            "vbnet",
            "verilog",
            "vhdl",
            "vim",
            "visualfoxpro",
            "visualprolog",
            "whitespace",
            "whois",
            "winbatch",
            "xml",
            "xorg_conf",
            "xpp",
            "z80"
        );
    }
}
