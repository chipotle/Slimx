<?php
/**
 * Extend Slim's native template system to allow for layouts. The layout
 * works like the original Ruby on Rails template system did; the rendered
 * template can be "wrapped" in a layout, and is inserted as the $content
 * variable.
 */
namespace Slimx;

class View extends \Slim\View
{
  protected $layout;

  /**
   * Pass the name of a layout to use as the argument to the constructor,
   * with a default of '_layout.php'. Passing boolean false will disable
   * the layout functionality entirely.
   * 
   * @param string|false $layout
   */
  public function __construct($layout=null)
  {
    if ($layout === null) {
      $layout = '_layout.php';
    }
    $this->layout = $layout;
    parent::__construct();
  }

  /**
   * Our (marginally) smarter render function. The layout can be changed by
   * setting a "_layout" data key to the new layout file, or boolean false to
   * disable layout.
   * 
   * @param string $template template name
   * @param array $data additional data to be passed to the template
   * @return string rendered template
   */
  public function render($template, $data = null)
  {
    $env = \Slim\Environment::getInstance();
    $this->setData('_base', $env['SCRIPT_NAME']);
    
    $data = array_merge($this->data->all(), (array)$data);
    $this->setData($data);
    
    if (isset($data['_layout'])) {
      $layout = $data['_layout'];
    } else {
      $layout = $this->layout;
    }
      
    $content = parent::render($template);
    if ($layout !== false) {
      $this->setData('content', $content);
      $content = parent::render($layout);
    }
    return $content;
  }
}
