<?php

namespace trochilidae\Sockets;

class Connection extends Resource {

    //TODO: Find best returns for if resource is closed or resource manager not assigned

    /**
     * @param ResourceManager $resourceManager
     */
    public function setResourceManager(ResourceManager $resourceManager)
    {
        //TODO: Think about messages already in buffer requiring specific protocols
        if (!is_null($this->resourceManager)) {
            $this->resourceManager->detach($this);
        }
        $this->resourceManager = $resourceManager;
        $resourceManager->attach($this);
    }

} 