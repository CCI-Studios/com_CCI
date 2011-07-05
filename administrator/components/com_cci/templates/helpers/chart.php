<?

class ComCCITemplateHelperChart extends KTemplateHelperAbstract
{
	
	public function display($config = array())
	{
		$config = new KConfig($config);
		$config->append(array(
			'data'		=> array(),
			'columns'	=> array(),
			'type'		=> 'BarChart',
			
			'width'		=> '100%',
			'height'	=> 500,
			
			'class'		=> '',
		))->append(array(
			'id'			=> 'googlechart-'.rand(),
		));
		
		$columns = '';
		foreach($config->columns as $name=>$type) {
			$columns .= "data.addColumn('{$type}', '{$name}');\n";
		}
		
		$doc = KFactory::get('lib.joomla.document');
		$doc->addScript('https://www.google.com/jsapi');
		$doc->addScriptDeclaration("google.load('visualization', '1', {'packages': ['corechart']});
		
			google.setOnLoadCallback(function () {
				var data = new google.visualization.DataTable({
					cols: {$config->columns->toJson()}
				});
				data.addRows({$config->data->toJson()});
				
				var chart = new google.visualization.{$config->type}(document.getElementById('{$config->id}'));
				chart.draw(data, {width: \"{$config->width}\", height: \"{$config->height}\"});
			});");

		return "<div id=\"{$config->id}\" class=\"{$config->class}\"></div>";
	}
}