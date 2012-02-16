<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\UnitOfWork;
use Entities;

/**
 * BasketElementRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BasketElementRepository extends EntityRepository
{

  public function findUserElement($element_id, \User_Adapter $user)
  {
    $dql = 'SELECT e, b, s, p, vd
            FROM Entities\BasketElement e
            JOIN e.basket b
            LEFT JOIN e.validation_datas vd
            LEFT JOIN b.validation s
            LEFT JOIN s.participants p
            WHERE b.usr_id = :usr_id AND e.id = :element_id';

    $params = array(
        'usr_id' => $user->get_id(),
        'element_id' => $element_id
    );

    $query = $this->_em->createQuery($dql);
    $query->setParameters($params);

    $cacheId = "_user_basket_element_" . $element_id . Entities\BasketElement::CACHE_SUFFIX;
    $query->useResultCache(true, 1800, $cacheId);

    $element = $query->getOneOrNullResult();

    return $element;
  }

  public function findElementsByRecord(\record_adapter $record)
  {
    $dql = 'SELECT e, b, s, p
            FROM Entities\BasketElement e
            JOIN e.basket b
            LEFT JOIN b.validation s
            LEFT JOIN s.participants p
            WHERE e.record_id = :record_id
            AND e.sbas_id = :sbas_id';

    $params = array(
        'sbas_id' => $record->get_sbas_id(),
        'record_id' => $record->get_record_id()
    );

    $query = $this->_em->createQuery($dql);
    $query->setParameters($params);
    $cacheId = "_basket_element_by_record_" . $record->get_serialize_key() . Entities\BasketElement::CACHE_SUFFIX;
    $query->useResultCache(true, 1800, $cacheId);

    return $query->getResult();
  }

  /**
   *
   * @param \record_adapter $record
   * @param \User_Adapter $user
   * @return \Doctrine\Common\Collections\ArrayCollection
   */
  public function findReceivedElementsByRecord(\record_adapter $record, \User_Adapter $user)
  {
    $dql = 'SELECT e, b, s, p
            FROM Entities\BasketElement e
            JOIN e.basket b
            LEFT JOIN b.validation s
            LEFT JOIN s.participants p
            WHERE b.usr_id = :usr_id
            AND b.pusher_id IS NOT NULL
            AND e.record_id = :record_id
            AND e.sbas_id = :sbas_id';

    $params = array(
        'sbas_id' => $record->get_sbas_id(),
        'record_id' => $record->get_record_id(),
        'usr_id' => $user->get_id()
    );

    $query = $this->_em->createQuery($dql);
    $query->setParameters($params);
    $cacheId = "_receveid_element_by_record_" . $record->get_serialize_key() . "_" . $user->getId() . Entities\BasketElement::CACHE_SUFFIX;
    $query->useResultCache(true, 1800, $cacheId);

    return $query->getResult();
  }

  public function findReceivedValidationElementsByRecord(\record_adapter $record, \User_Adapter $user)
  {
    $dql = 'SELECT e, b, v, p
            FROM Entities\BasketElement e
            JOIN e.basket b
            JOIN b.validation v
            JOIN v.participants p
            WHERE p.usr_id = :usr_id
            AND e.record_id = :record_id
            AND e.sbas_id = :sbas_id';

    $params = array(
        'sbas_id' => $record->get_sbas_id(),
        'record_id' => $record->get_record_id(),
        'usr_id' => $user->get_id()
    );

    $query = $this->_em->createQuery($dql);
    $query->setParameters($params);
    $cacheId = "_receveid_validation_element_by_record" . $record->get_serialize_key() . "_" . $user->getId() . Entities\BasketElement::CACHE_SUFFIX;
    $query->useResultCache(true, 1800, $cacheId);

    return $query->getResult();
  }

  /**
   *
   * @param type $element_id
   * @param \User_Adapter $user
   * @return \Entities\BasketELement
   */
  public function findElement($element_id, \User_Adapter $user)
  {
    $dql = 'SELECT e, b, s, p
            FROM Entities\BasketElement e
            JOIN e.basket b
            LEFT JOIN b.validation s
            LEFT JOIN s.participants p
            WHERE e.id = :element_id';

    $query = $this->_em->createQuery($dql);

    $query->setParameters(array('element_id' => $element_id));
    $cacheId = "_validation_element" . $element_id . Entities\BasketElement::CACHE_SUFFIX;
    $query->useResultCache(true, 1800, $cacheId);

    $element = $query->getOneOrNullResult();

    /* @var $element \Entities\BasketElement */
    if (null === $element)
    {
      throw new \Exception_NotFound(_('Element is not found'));
    }

    if ($element->getBasket()->getowner()->get_id() != $user->get_id())
    {
      throw new \Exception_Forbidden(_('You have not access to this basket element'));
    }

    return $element;
  }

}
