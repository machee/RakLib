<?php

/*
 * RakLib network library
 *
 *
 * This project is not affiliated with Jenkins Software LLC nor RakNet.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

declare(strict_types=1);

namespace raklib\protocol;

#include <rules/RakLibPacket.h>

class OpenConnectionReply1 extends OfflineMessage{
	public static $ID = MessageIdentifiers::ID_OPEN_CONNECTION_REPLY_1;

	/** @var int */
	public $serverID;
	/** @var bool */
	public $serverSecurity = false;
	/** @var int */
	public $mtuSize;

	protected function encodePayload() : void{
		$this->writeMagic();
		$this->putLong($this->serverID);
		$this->putByte($this->serverSecurity ? 1 : 0);
		$this->putShort($this->mtuSize);

		/*
		 * When connecting via some VPNs which use compression, the initial connection request can get compressed, which
		 * messes up path MTU and can cause a larger than expected MTU to be established, which then causes the
		 * connection to break as soon as either party sends a large packet which doesn't compress.
		 *
		 * This is a hack to work around that - by appending random bytes to the end of the buffer, we can properly
		 * verify the path MTU without compression interfering. If this packet is too large to reach the client, then
		 * it will be dropped and the client will send a connection request with smaller MTU size.
		 */
		$this->put(random_bytes($this->mtuSize - strlen($this->buffer) - 28));
	}

	protected function decodePayload() : void{
		$this->readMagic();
		$this->serverID = $this->getLong();
		$this->serverSecurity = $this->getByte() !== 0;
		$this->mtuSize = $this->getShort();
	}
}
