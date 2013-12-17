<?php

/**
 * Controller Extra Bundle
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 * @since 2013
 */

namespace Mmoreram\ControllerExtraBundle\EventListener\Abstracts;

use ReflectionMethod;
use Doctrine\Common\Annotations\Reader;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpFoundation\Request;

use Mmoreram\ControllerExtraBundle\Annotation\Abstracts\Annotation;


/**
 * Abstract Event Listener
 */
abstract class AbstractEventListener
{

    /**
     * @var KernelInterface
     *
     * Kernel
     */
    protected $kernel;


    /**
     * @var Reader
     *
     * Annotation Reader
     */
    protected $reader;


    /**
     * @var boolean
     *
     * Current annotation must be evaluated
     */
    protected $active;


    /**
     * @var array
     *
     * Method parameters indexed
     */
    protected $parametersIndexed = array();


    /**
     * Construct method
     *
     * @param KernelInterface $kernel Kernel
     * @param Reader          $reader Reader
     */
    public function __construct(KernelInterface $kernel, Reader $reader)
    {
        $this->kernel = $kernel;
        $this->reader = $reader;
    }


    /**
     * Specific annotation evaluation.
     *
     * This method must be implemented in every single EventListener with specific logic
     *
     * @param boolean $active Define if current annotation must be evaluated
     *
     * @return AbstractEventListener self Object
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }


    /**
     * Return kernel object
     *
     * @return KernelInterface Kernel
     */
    protected function getKernel()
    {
        return $this->kernel;
    }


    /**
     * Return reader
     *
     * @return Reader Reader
     */
    protected function getReader()
    {
        return $this->reader;
    }


    /**
     * Return parameters indexed
     *
     * @return array Parameters indexed
     */
    protected function getParametersIndexed()
    {
        return $this->parametersIndexed;
    }


    /**
     * Return active value
     *
     * @return boolean Current annotation parsing is active
     */
    protected function isActive()
    {
        return $this->active;
    }


    /**
     * Method executed while loading Controller
     *
     * @param FilterControllerEvent $event Filter Controller event
     *
     * @todo place all non-specific data in a service
     */
    public function onKernelController(FilterControllerEvent $event)
    {

        if (!$this->isActive()) {

            return;
        }

        /**
         * Data load
         */
        $controller = $event->getController();

        /**
         * If is not a valid controller structure, return
         */
        if (!is_array($controller)) {

            return;
        }

        $request = $event->getRequest();
        $method = new ReflectionMethod($controller[0], $controller[1]);

        /**
         * Method parameteres load.
         * A hash is created to access to all needed parameters with cost O(1)
         */
        $parameters = $method->getParameters();

        foreach ($parameters as $parameter) {

            $this->parametersIndexed[$parameter->getName()] = $parameter;
        }

        /**
         * Given specific configuration, analyze full request
         */
        $this->analyzeRequest($request, $this->getReader(), $controller, $method, $parametersIndexed);
    }


    /**
     * Evaluate request
     *
     * @param Request          $request           Request
     * @param Reader           $reader            Reader
     * @param array            $controller        Controller
     * @param ReflectionMethod $method            Method
     * @param array            $parametersIndexed Parameters indexed
     */
    public function analyzeRequest(Request $request, Reader $reader, array $controller, \ReflectionMethod $method, array $parametersIndexed)
    {
        /**
         * Annotations load
         */
        $methodAnnotations = $reader->getMethodAnnotations($method);

        /**
         * Every annotation found is parsed
         */
        foreach ($methodAnnotations as $annotation) {

            if ($annotation instanceof Annotation) {

                $this->evaluateAnnotation($controller, $request, $annotation, $parametersIndexed);
            }
        }
    }


    /**
     * Specific annotation evaluation.
     *
     * This method must be implemented in every single EventListener with specific logic
     *
     * All method code will executed only if specific active flag is true
     *
     * @param array      $controller        Controller
     * @param Request    $request           Request
     * @param Annotation $annotation        Annotation
     * @param array      $parametersIndexed Parameters indexed
     *
     * @return AbstractEventListener self Object
     */
    abstract public function evaluateAnnotation(array $controller, Request $request, Annotation $annotation, array $parametersIndexed);
}
