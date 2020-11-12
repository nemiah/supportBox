<?php
/**
 *  This file is part of ubiquitous.

 *  ubiquitous is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.

 *  ubiquitous is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses></http:>.
 * 
 *  2007 - 2020, open3A GmbH - Support@open3A.de
 */

class mHilfeGUI extends UnpersistentClass implements iGUIHTMLMP2 {
	
	private $target = "http://data.open3a.de/?section=hilfe&";
	#private $target = "http://hilfe.furtmeier.it/?";
	
	public function getHTML($id, $page){
		T::load(__DIR__, "mHilfe");
		$GUI = new HTMLColGUI($this);
		$GUI->cols(3);
		$GUI->content("right", $this->contentRight());
		
		$GUI->widths(array("left" => "55%", "center" => "25%", "right" => "20%"));
		
		return $GUI;
	}

	public function contentRight(){
		$plugins = file_get_contents($this->target."app=".Applications::activeApplication());
		if($plugins === false){
			return "<p class=\"error\">Der Server ist nicht erreichbar!</p>
				<p>Anleitungen zu den Plugins finden Sie auch im <a target=\"_blank\" href=\"https://blog.furtmeier.it\">Blog</a>.</p>";
		}
		$plugins = json_decode($plugins);
		
		$allPlugins = $_SESSION["CurrentAppPlugins"]->getAllMenuEntries();

		$B = new Button("Plugin auswählen", "arrow_left", "iconic");
		$T = new HTMLTable(2, "Themen");
		$T->weight("light");
		$T->useForSelection();
		$T->setColWidth(1, 20);
		
		$T->addRow(array($B, T::_("Die ersten Schritte")));
		$T->addCellStyle(2, "padding-top:5px;padding-bottom:5px;");
		$T->addRowEvent("click", "contentManager.selectRow(this); ".OnEvent::rme($this, "contentCenter", array(4, "'kid'"), "function(t){ \$j('#contentScreenCenter').html(t.responseText); }"));

		
		
		foreach($plugins AS $P){
			$B = new Button("Plugin auswählen", "arrow_left", "iconic");
			
			$T->addRow(array($B, T::_($P->plugin)));
			$T->addCellStyle(2, "padding-top:5px;padding-bottom:5px;");
			$T->addRowEvent("click", "contentManager.selectRow(this); ".OnEvent::rme($this, "contentCenter", array($P->cid), "function(t){ \$j('#contentScreenCenter').html(t.responseText); }"));
			
			if(!isset($allPlugins[$P->plugin]))
				$T->addRowStyle ("color:grey;");
		}
		
		return $T;
	}
	
	public function contentLeft($pid, $inPopup = false){
		$article = file_get_contents($this->target."pid=".$pid);
		
		if($inPopup){
			$B = new Button("Eintrag\nausdrucken", "../images/navi/printer.png");
			$B->style("position:fixed;right:40px;top:30px;");
			$B->onclick("window.print();");
			$B->className("backgroundColor3 no-print");

			#die(Util::getBasicHTML($this->css()."$B<div style=\"background-color:white;\">".$article."<div style=\"clear:both;\"></div></div>", "Hilfe", false));
			die(Util::getBasicHTML($this->css()."$B<div class=\"backgroundGrey\"><div style=\"background-color:white;\" class=\"margins\">".$article."<div style=\"clear:both;\"></div></div></div>", "Hilfe", false));
		}
		
		$B = new Button("Im Fenster\nöffnen", "./ubiquitous/Hilfe/popup.png");
		$B->style("margin:10px;position:absolute;margin-top:30px;");
		$B->windowRme("mHilfe", "-1", "contentLeft", array($pid, 1));
		$B->id("buttonWindow");
		
		echo $this->css().$B."<div class=\"backgroundGrey\"><div style=\"background-color:white;\" class=\"margins\">".$article."<div style=\"clear:both;\"></div></div></div>".OnEvent::script("\$j('#buttonWindow').css('margin-left', parseInt(\$j('#contentScreenLeft').css('width')) - 200);\$j('.backgroundGrey').css('min-height', contentManager.maxHeight());");
	}
	
	private function css(){
		return "<style type=\"text/css\">
			.margins {
				margin-left:30px;
				margin-right:30px;
				padding-right:1em;
				padding-left:calc(1em - 5px);
				box-shadow:0px 0px 10px #777;
				padding-bottom:2em;
			}
			
			.backgroundGrey {
				background-color:#BBB;
				padding-bottom:20px;
				padding-top:20px;
			}

			img {
				vertical-align:middle;
			}
			
			h1, h2, h3, h4 { 
				clear:both;
			}
			
			h3 {
				padding-top:1.2em;
			}
			
			div h3:first-child {
				padding-top:0em;
			}
			
			@media print {    
				.no-print
				{
					display: none !important;
				}
				h1:first-of-type { 
					page-break-before: avoid;
				}
				h1 { 
					page-break-before: always;
					clear:both;
				}
				h2, h3, h4 {
					page-break-after: avoid;
					clear:both;
				}
				pre, blockquote {
					page-break-inside: avoid;
				}
				.margins {
					margin-left:0px;
					margin-right:0px;
					box-shadow:none;
				}
				
				.backgroundGrey {
					background-color:white;
					padding-bottom:0;
					padding-top:0;
				}
			}
			</style>";
	}
	
	public function contentCenter($cid, $mode = "cid"){
		$articles = file_get_contents($this->target."$mode=".$cid);
		$articles = json_decode($articles);

		$T = new HTMLTable(2, "Artikel");
		$T->weight("light");
		$T->useForSelection();
		$T->setColWidth(1, 20);
		
		foreach($articles AS $P){
			$B = new Button("Artikel auswählen", "arrow_left", "iconic");
			
			$T->addRow(array($B, $P->title));
			$T->addCellStyle(2, "padding-top:5px;padding-bottom:5px;");
			$T->addRowEvent("click", "contentManager.selectRow(this); ".OnEvent::rme($this, "contentLeft", array($P->pid), "function(t){ \$j('#contentScreenLeft').html(t.responseText); }"));
		}
		
		echo $T;
		
	}

	public function firstSteps(){
		mUserdata::setUserdataS("firstStepsSeen", "1");
		
		$articles = file_get_contents($this->target."kid=4");
		$articles = json_decode($articles);

		$pids = [];
		foreach($articles AS $item)
			$pids[] = $item->pid;
		
		$article = file_get_contents($this->target."pid=".implode(",", $pids));
		
		$B = new Button("Eintrag\nausdrucken", "../images/navi/printer.png");
		$B->style("position:fixed;right:40px;top:30px;");
		$B->onclick("window.print();");
		$B->className("backgroundColor3 no-print");
		
		echo Util::getBasicHTML($this->css(true)."$B<div class=\"backgroundGrey\"><div style=\"background-color:white;\" class=\"margins\">".$article."<div style=\"clear:both;\"></div></div></div>", "Hilfe", false);
	}
}
?>