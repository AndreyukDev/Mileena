<?php

declare(strict_types=1);

namespace Mileena\Web;

abstract class View
{
    protected static $view = null;

    private array $data = [];

    public function __construct(
        public readonly string $templateDir,
    ) {}

    public function assign(string $key, mixed $value): self
    {
        $this->data[$key] = $value;

        return $this;
    }

    public function fetch(string $template): string
    {
        if (!file_exists($this->templateDir . $template)) {
            throw new \Exception("Template not found: $template");
        }

        extract($this->data);

        ob_start();

        include $this->templateDir . $template;

        return ob_get_clean();
    }

    public function display(string $template): void
    {
        echo $this->fetch($template);
    }

    abstract public static function getView(): View;
}
