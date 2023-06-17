<?php

namespace App\Models;
use \Exception;

class EthernetSwitch
{
    protected $ip, $community, $retries, $timeout;
    protected $baseport2ifIndex, $ifIndex2name;

    const DEFAULT_RETRIES = 1;
    const DEFAULT_TIMEOUT = 1000000; /* microsec */

    const MACTABLE_OID        = '.1.3.6.1.2.1.17.4.3'; /* BRIDGE-MIB::dot1dTpFdbTable */
    const MACTABLE_MAC_OID    = '.1.3.6.1.2.1.17.4.3.1.1'; /* BRIDGE-MIB::dot1dTpFdbAddress */
    const MACTABLE_PORT_OID   = '.1.3.6.1.2.1.17.4.3.1.2'; /* BRIDGE-MIB::dot1dTpFdbPort */
    const BASEPORTASINDEX_OID = '.1.3.6.1.2.1.17.1.4.1.2'; /* BRIDGE-MIB::dot1dBasePortAsIndex */
    const IFNAME_OID	      = '.1.3.6.1.2.1.31.1.1.1.1'; /* ifName */
    const IFALIAS_OID         = '.1.3.6.1.2.1.31.1.1.1.18'; /* ifAlias */

    function __construct($ip, $community, $options = [])
    {
        $this->ip = $ip;
        $this->community = $community;
        $this->retries = self::DEFAULT_RETRIES;
        $this->timeout = self::DEFAULT_TIMEOUT;
        $this->baseport2ifIndex = [];

        if ( !function_exists("snmp2_walk") )
            throw new Exception("SNMP functions not available. Try: sudo apt install php-snmp");

        snmp_set_oid_numeric_print(SNMP_OID_OUTPUT_NUMERIC);
        snmp_set_quick_print(true);
        snmp_set_enum_print(true);
        snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
    }

    /* Get port name by ifIndex
       Results are cached, so multiple calls will not result in multiple SNMP queries */
    function getPortNameByIfIndex($ifIndex)
    {
        if (!$ifIndex)
            return false;

        if (!isset($this->ifIndex2name[$ifIndex]))
        {
            /* Return user configured name (ifAlias) if available, otherwise return system defined ifName */
            $ifAlias = $this->_get(self::IFALIAS_OID.'.'.$ifIndex);
            if ($ifAlias)
                $this->ifIndex2name[$ifIndex] = $ifAlias;
            else
                $this->ifIndex2name[$ifIndex] = $this->_get(self::IFNAME_OID.'.'.$ifIndex);
        }

        return $this->ifIndex2name[$ifIndex];
    }

    /* Get ifIndex number by BRIDGE-MIB baseport number
       Results are cached, so multiple calls will not result in multiple SNMP queries */
    function getIfIndexByBasePort($baseport)
    {
        if (!$baseport)
            return false;

        if (!isset($this->baseport2ifIndex[$baseport]))
        {
            $this->baseport2ifIndex[$baseport] = $this->_get(self::BASEPORTASINDEX_OID.'.'.$baseport);
        }

        return $this->baseport2ifIndex[$baseport];
    }

    function getMac2basePortTable()
    {
		$mac_addresses = array();
		$baseports	   = array();
		$result		   = array();

		$data  = $this->_walk(self::MACTABLE_OID);
		$l     = strlen(self::MACTABLE_MAC_OID);
        if (!$data)
            return false;

		foreach ($data as $oid => $v)
		{
			if ( !strncmp($oid, self::MACTABLE_MAC_OID, $l) )
			{
				$nr                 = substr($oid, $l+1);
				$mac_addresses[$nr] = $this->_bin2mac($v);
			}
			else if ( !strncmp($oid, self::MACTABLE_PORT_OID, $l) )
			{
				$nr             = substr($oid, $l+1);
				$baseports[$nr] = $v;				
			}
		}

        foreach ($mac_addresses as $id => $mac)
        {
            if (isset($result[$mac]))
            {
                /* MAC address is seen on multiple ports */
                $result[$mac] = false;
            }
            else if (isset($baseports[$id]))
            {
                $result[$mac] = $baseports[$id];
            }
        }

        return $result;
    }

    function getMac2portNameTable()
    {
        $result = $this->getMac2basePortTable();
        if (!$result)
            return false;

        foreach ($result as $mac => $baseport)
        {
            if (!$baseport)
                $result[$mac] = '[multiple ports]';
            else
                $result[$mac] = $this->getPortNameByIfIndex($this->getIfIndexByBasePort($baseport));
        }

        return $result;
    }

    function getPortNameByMac($m)
    {
        $mac2baseport = $this->getMac2basePortTable();
        foreach ($mac2baseport as $mac => $baseport)
        {
            if ($mac == $m)
            {
                if ($baseport)
                    return $this->getPortNameByIfIndex($this->getIfIndexByBasePort($baseport));
                else
                    return '[multiple ports]';
            }
        }

        return false;
    }

    function _bin2mac($binary)
    {
        if (strlen($binary) != 6)
            throw new Exception("Switch returned invalid MAC address (len != 6)");

        return sprintf("%02x:%02x:%02x:%02x:%02x:%02x", ord($binary[0]), ord($binary[1]), ord($binary[2]),
                                                        ord($binary[3]), ord($binary[4]), ord($binary[5]));
    }

    function _mac2bin($mac)
    {
        return implode('', array_map("hex2bin", explode(':', $mac)));
    }

    function _get($oid)
    {
        return @snmp2_get($this->ip, $this->community, $oid, $this->timeout, $this->retries);
    }

    function _walk($oid)
    {
        return @snmp2_real_walk($this->ip, $this->community, $oid, $this->timeout, $this->retries);
    }    
}
