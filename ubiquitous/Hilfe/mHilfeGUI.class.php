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
 *  2007 - 2018, Furtmeier Hard- und Software - Support@Furtmeier.IT
 */

class mHilfeGUI extends UnpersistentClass implements iGUIHTMLMP2 {

	public function getHTML($id, $page){
		
		$GUI = new HTMLColGUI($this);
		$GUI->cols(3);
		$GUI->content("right", $this->contentRight());
		
		$GUI->widths(array("left" => "55%", "center" => "25%", "right" => "20%"));
		
		return $GUI;
	}

	public function contentRight(){
		$plugins = file_get_contents("http://hilfe.furtmeier.it/?app=".Applications::activeApplication());
		if($plugins === false){
			return "<p class=\"error\">Der Server ist nicht erreichbar!</p>
				<p>Anleitungen zu den Plugins finden Sie auch im <a target=\"_blank\" href=\"https://blog.furtmeier.it\">Blog</a>.</p>";
		}
		$plugins = json_decode($plugins);
		
		$allPlugins = $_SESSION["CurrentAppPlugins"]->getAllMenuEntries();

		$T = new HTMLTable(2, "Plugins");
		$T->weight("light");
		$T->useForSelection();
		$T->setColWidth(1, 20);
		
		foreach($plugins AS $P){
			$B = new Button("Plugin auswählen", "arrow_left", "iconic");
			
			$T->addRow(array($B, $P->plugin));
			$T->addCellStyle(2, "padding-top:5px;padding-bottom:5px;");
			$T->addRowEvent("click", "contentManager.selectRow(this); ".OnEvent::rme($this, "contentCenter", array($P->cid), "function(t){ \$j('#contentScreenCenter').html(t.responseText); }"));
			
			if(!isset($allPlugins[$P->plugin]))
				$T->addRowStyle ("color:grey;");
		}
		
		return $T;
	}
	
	public function contentLeft($pid, $inPopup = false){
		$article = file_get_contents("http://hilfe.furtmeier.it/?pid=".$pid);
		
		$B = new Button("Im Fenster\nöffnen", "./ubiquitous/Hilfe/popup.png");
		$B->style("float:right;margin:10px;margin-top:25px;");
		$B->windowRme("mHilfe", "-1", "contentLeft", array($pid, 1));
		
		if($inPopup)
			die(Util::getBasicHTML("<div style=\"background-color:white;\">".$article."<div style=\"clear:both;\"></div></div>", "Hilfe", false));
		
		echo $B.$article;
	}
	
	public function contentCenter($cid){
		$articles = file_get_contents("http://hilfe.furtmeier.it/?cid=".$cid);
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

}
?>