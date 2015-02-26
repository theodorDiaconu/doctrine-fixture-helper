<?php

/**
 * @author Theodor Diaconu
 */
namespace TD\FixtureHelper;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use TD\FixtureHelper\Traits\SlugifyTrait;

class BaseFixture extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
    use SlugifyTrait;

    /** @var  Container */
    protected $container;
    /** @var  ObjectManager */
    protected $manager;
    static $nest = 0;
    static $cache = [];

    protected $faker;

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
            
        if (method_exists($this, 'doLoad')) {
            call_user_func([$this, 'doLoad']);
        }
    }

    function getOrder()
    {
        return 0;
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->faker     = Factory::create();
    }

    /**
     * @param          $name
     * @param callable $callback
     * @param null     $ref
     *
     * @throws \InvalidArgumentException
     * @return void|null|object
     */
    public function iterator($name, callable $callback, $ref = null)
    {
        if (is_int($name)) {
            $this->handleIntegerIterator($name, $callback, $ref, microtime(true));
        } elseif (is_string($name)) {
            $this->handleStringIterator($name, $callback, microtime(true));
        } else {
            throw new \InvalidArgumentException;
        }

        $this->manager->flush();
    }

    /**
     * @param $time
     *
     * @return string
     */
    protected function formatTime($time)
    {
        return number_format($time, 5, '.');
    }

    /**
     * @param          $name
     * @param callable $callback
     * @param          $ref
     */
    protected function countIteration($name, callable $callback, $ref)
    {
        $count = 0;
        if ($ref) {
            $increase = isset(self::$cache[$ref]) ? self::$cache[$ref] : 0;
            while ($count++ < $name) {
                $object = $callback($count);
                $this->setReference($ref . '-' . $increase++, $object);

                if ($this->manager) {
                    $this->manager->persist($object);
                    if ($this->faker->boolean(5)) {
                        $this->manager->flush();
                    }
                }
            }
            static::$cache[$ref] = $increase;
        } else {
            while ($count++ < $name) {
                $callback($count);
            }
        }
    }

    /**
     * @param          $name
     * @param callable $callback
     *
     * @return mixed
     */
    protected function referenceIteration($name, callable $callback)
    {
        $count = 0;
        while ($this->hasReference($name . '-' . $count)
            && ($object = $this->getReference($name . '-' . $count++))) {
            $callback($object);
        }
        return $count;
    }

    /**
     * Logging the messages
     *
     * @param $isStarting
     * @param $message
     */
    private function log($isStarting, $message)
    {
        if (!$isStarting) {
            static::$nest--;
        }

        $tabPrefix = str_repeat("\t", static::$nest);
        printf('%s%s : %s'."\n", $tabPrefix, $isStarting ? '[Start] ' : '[End] ', $message);

        if ($isStarting) {
            static::$nest++;
        }
    }

    /**
     * @param $name
     * @param callable $callback
     * @param $ref
     * @param $time_start
     */
    protected function handleIntegerIterator($name, callable $callback, $ref, $time_start)
    {
        $this->log(true, sprintf('Iteration for %s records having reference "%s".', $name, $ref));

        $this->countIteration($name, $callback, $ref);

        $this->log(false, sprintf(
            'Iteration for "%s" records having reference "%s" and it lasted "%s".',
            $name,
            $ref,
            $this->formatTime(microtime(true) - $time_start)
        ));
    }

    /**
     * @param $name
     * @param callable $callback
     * @param $time_start
     */
    protected function handleStringIterator($name, callable $callback, $time_start)
    {
        $this->log(true, sprintf('Iterating through all the "%s" references', $name));

        $count = $this->referenceIteration($name, $callback);

        $this->log(false, sprintf(
            'Iteration for all "%s" records having reference "%s" and it lasted "%s".',
            $count - 1,
            $name,
            $this->formatTime(microtime(true) - $time_start)
        ));
    }


    /**
     * Returns the list of objects for a given reference.
     * @param $ref
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getObjects($ref)
    {
        if (!array_key_exists($ref, static::$cache)) {
            throw new \InvalidArgumentException('The reference does not exist');
        }

        // build obj array
        $objects = [];
        $this->iterator(static::$cache[$ref], function($index) use ($ref, &$objects) {
            $objects[] = $this->getReference($ref.'-'.($index-1));
        });

        return $objects;
    }

    /**
     * @param $ref
     *
     * @return object
     * @throws \InvalidArgumentException
     */
    public function getRandomObject($ref)
    {
        if (!array_key_exists($ref, static::$cache)) {
            throw new \InvalidArgumentException('The reference does not exist');
        }

        $index = rand(0, static::$cache[$ref] - 1);

        return $this->getReference($ref.'-'.$index);
    }
}