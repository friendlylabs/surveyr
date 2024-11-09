<?php

function bladeInit(){
    return new Class {
        protected $blade;
    
        public function __construct()
        {
            // Initialize Blade instance with configuration
            $this->blade = \Leaf\Config::get('views.blade')->configure(
                $this->getViewPath(),
                $this->getCachePath()
            );
            
            // Add .blade.js extension
            $this->blade->addExtension('blade.js', 'blade');
            $this->blade->addExtension('blade.css', 'blade');
    
            // Register custom directives
            $this->registerStyleDirective();
            $this->registerRouteDirective();
            $this->registerScriptDirective();
            $this->registerDirectiveActive();
        }
    
        protected function getViewPath()
        {
            return AppConfig('views.path');
        }
    
        protected function getCachePath()
        {
            return AppConfig('views.cachePath');
        }
    
        protected function registerRouteDirective()
        {
            // Define @route directive
            $this->blade->compiler()->directive('route', function ($expression) {
                return "<?php echo route($expression); ?>";
            });
        }
    
        protected function registerScriptDirective()
        {
            // Define @script directive
            $this->blade->compiler()->directive('script', function ($expression) {
                $params = explode(',', trim($expression, "()'\""));
                $view = trim($params[0], "'\" ");
                $type = isset($params[1]) ? trim($params[1], "'\" ") : 'script';

                if($type == 'src'){
                    return <<<HTML
                    <?php
                        \$__env->startPush('scripts');
                        echo '<script src="' . '$view' . '"></script>' . PHP_EOL;
                        \$__env->stopPush();
                    ?>
                    HTML;
                }

                elseif($type == 'srcmodule'){
                    return <<<HTML
                    <?php
                        \$__env->startPush('scripts');
                        echo '<script type="module" src="' . '$view' . '"></script>' . PHP_EOL;
                        \$__env->stopPush();
                    ?>
                    HTML;
                }

                // if($type == 'module') $scriptTag = "<script type=\'module\'>";
                $scriptTag = (function() use ($type){
                    if($type == 'module') return "<script type='module'>";
                    if($type == 'importmap') return "<script type='importmap'>";
                    return "<script>";
                })();
            
                return <<<HTML
                <?php
                    \$view = str_replace('.', '/', '$view');
                    \$filePath = trim(ViewsPath(\$view . '.blade.js'), '//');
                    // if (file_exists(\$filePath)) {
                        \$__env->startPush('scripts');
                        echo '$scriptTag' . PHP_EOL;
                        echo \$__env->make('$view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render() . PHP_EOL;
                        echo chr(60) . "/script" . chr(62) . PHP_EOL;
                        \$__env->stopPush();
                    //}else{
                        //echo "<!-- Blade.js file not found: \$filePath -->";
                    //}
                ?>
                HTML;
            });
            
        }

        protected function registerStyleDirective()
        {
            // Define @style directive
            $this->blade->compiler()->directive('style', function ($expression) {
                $params = explode(',', trim($expression, "()'\""));
                $view = trim($params[0], "'\" ");
                $type = isset($params[1]) ? trim($params[1], "'\" ") : 'style';

                if($type == 'src'){
                    return <<<HTML
                    <?php
                        \$__env->startPush('styles');
                        echo '<link rel="stylesheet" href="' . '$view' . '">' . PHP_EOL;
                        \$__env->stopPush();
                    ?>
                    HTML;
                }
            
                $styleTag = "<style>";
            
                return <<<HTML
                <?php
                    \$view = str_replace('.', '/', '$view');
                    \$filePath = trim(ViewsPath(\$view . '.blade.css'), '//');
                    // if (file_exists(\$filePath)) {
                        \$__env->startPush('styles');
                        echo '$styleTag' . PHP_EOL;
                        echo \$__env->make('$view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render() . PHP_EOL;
                        echo chr(60) . "/style" . chr(62) . PHP_EOL;
                        \$__env->stopPush();
                    //}else{
                        //echo "<!-- Blade.css file not found: \$filePath -->";
                    //}
                ?>
                HTML;
            });
        }

        protected function registerDirectiveActive()
        {
            $this->blade->compiler()->directive('active', function ($expression) {
                return "<?php echo active($expression); ?>";
            });
        }
    
        public function getBladeInstance()
        {
            return $this->blade;
        }
    };
}

bladeInit();