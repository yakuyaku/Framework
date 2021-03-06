<?php
namespace Wandu\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Psr\SimpleCache\CacheInterface;
use ReflectionClass;

class AnnotationManager
{
    /** @var \Doctrine\Common\Annotations\Reader */
    protected $reader;
    
    /** @var \Psr\SimpleCache\CacheInterface */
    protected $cache;

    /** @var string */
    protected $cachePrefix;
    
    /**
     * @param \Doctrine\Common\Annotations\Reader $reader
     * @param \Psr\SimpleCache\CacheInterface $cache
     * @param string $cachePrefix
     */
    public function __construct(Reader $reader = null, CacheInterface $cache = null, string $cachePrefix = 'annotation.')
    {
        $this->reader = $reader ?? new AnnotationReader();
        $this->cache = $cache;
        $this->cachePrefix = $cachePrefix;
    }

    /**
     * @param string $className
     * @return \Wandu\Annotation\AnnotationBag
     */
    public function read(string $className): AnnotationBag
    {
        if ($this->cache && $this->cache->has($className)) {
            return $this->cache->get($this->cachePrefix . $className);
        }
        $reflClass = new ReflectionClass($className);
        $classAnnotations = [];
        foreach ($this->reader->getClassAnnotations($reflClass) as $classAnnotation) {
            $classAnnotations[] = $classAnnotation;
        }
        $propsAnnotations = [];
        foreach ($reflClass->getProperties() as $reflProperty) {
            $propsAnnotations[$reflProperty->getName()] = [];
            foreach ($this->reader->getPropertyAnnotations($reflProperty) as $propertyAnnotation) {
                $propsAnnotations[$reflProperty->getName()][] = $propertyAnnotation;
            }
        }
        $methodsAnnotations = [];
        foreach ($reflClass->getMethods() as $reflMethod) {
            $methodsAnnotations[$reflMethod->getName()] = [];
            foreach ($this->reader->getMethodAnnotations($reflMethod) as $methodAnnotation) {
                $methodsAnnotations[$reflMethod->getName()][] = $methodAnnotation;
            }
        }
        $result = new AnnotationBag($classAnnotations, $propsAnnotations, $methodsAnnotations);
        if ($this->cache) {
            $this->cache->set($this->cachePrefix . $className, $result);
        }
        return $result;
    }
}
