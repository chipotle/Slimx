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
    if ($layout === null) $layout = '_layout.php';
    $this->layout = $layout;
  }

  /**
   * Our (marginally) smarter render function. The layout can be changed by
   * setting a "_layout" data key to the new layout file, or boolean false to
   * disable layout.
   * 
   * @param string $template template name
   * @return string rendered template
   */
  public function render($template)
  {
    $env = \Slim\Environment::getInstance();
    $this->setData('_base', $env['SCRIPT_NAME']);
    $data = $this->getData();
    if (isset($data['_layout'])) $layout = $data['_layout'];
    else $layout = $this->layout;
    $content = parent::render($template);
    if ($layout !== false) {
      $this->setData($data);
      $this->setData('content', $content);
      $content = parent::render($layout);
    }
    return $content;
  }
}