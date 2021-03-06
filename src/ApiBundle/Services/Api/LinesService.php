<?php
namespace ApiBundle\Services\Api;

use ApiBundle\Helper\NetworkHelper;
use FOS\RestBundle\Exception\InvalidParameterException;
use Ratp\Api;
use Ratp\Line;
use Ratp\Lines;

class LinesService extends ApiService implements ApiDataInterface
{
    /**
     * @var int $resultTtl
     */
    private $resultTtl;

    /**
     * LinesService constructor.
     * We override parent constructor to inject ttl.
     *
     * @param $ttl
     * @param $resultTtl
     */
    public function __construct($ttl, $resultTtl)
    {
        $this->resultTtl = $resultTtl;

        parent::__construct($ttl);
    }

    /**
     * @param $method
     * @param array $parameters
     * @return mixed
     */
    public function get($method, $parameters = [])
    {
        return parent::getData($method, $parameters);
    }

    /**
     * @return array
     */
    protected function getAll()
    {
        return $this->getLinesCache();
    }

    /**
     * @param $parameters
     * @return array|null
     */
    protected function getSpecific($parameters)
    {
        $typesAllowed = [
            'rers',
            'metros',
            'tramways',
            'bus',
            'noctiliens'
        ];

        if (!in_array($parameters['type'], $typesAllowed)) {
            throw new InvalidParameterException(sprintf('Unknown type : %s', $parameters['type']));
        }

        $data = $this->getLinesCache();

        return [
            $parameters['type'] => $data[$parameters['type']]
        ];
    }

    /**
     * @param $parameters
     * @return array|null
     */
    protected function getLine($parameters)
    {
        $typesAllowed = [
            'rers',
            'metros',
            'tramways',
            'bus',
            'noctiliens'
        ];

        if (!in_array($parameters['type'], $typesAllowed)) {
            throw new InvalidParameterException(sprintf('Unknown type : %s', $parameters['type']));
        }

        $data = $this->getLinesCache();

        $lines = [];

        foreach ($data[$parameters['type']] as $dataLine) {
            if (strtolower($dataLine['code']) == strtolower($parameters['code'])) {
                $lines[] = $dataLine;
            }
        }

        return $lines;
    }

    /**
     * @return array
     */
    private function getLinesCache()
    {
        $cache = $this->storage->getCacheItem('lines_data');

        if ($cache->isHit()) {
            $data = unserialize($cache->get());
        } else {
            $data = $this->getAllLinesForCache();
            $this->storage->setCache($cache, $data, $this->resultTtl);
        }
        return $data;
    }

    /**
     * @return array
     */
    private function getAllLinesForCache()
    {
        $return = [];

        $lines = new Lines();
        $api   = new Api();

        foreach ($api->getLines($lines)->getReturn() as $line) {
            /** @var Line $line */
            if ($line instanceof Line) {
                $type = NetworkHelper::typeSlug($line->getReseau()->getCode());

                if ($type) {
                    $return[$type][] = [
                        'code'       => str_replace(['N', 'T'], '', $line->getCode()),
                        'name'       => $line->getReseau()->getName() . ' ' . $line->getCode(),
                        'directions' => $line->getName(),
                        'id'         => $line->getId(),
                    ];
                }
            }
        }

        return $return;
    }
}
