<?php

namespace Shopware\Components\SwagImportExport\Utils;

class TreeHelper
{

    /**
     * Converts the JSON tree to ExtJS tree
     * 
     * @param array $node
     * @param boolean $isInIteration
     * @param string $adapter
     * @return array
     */
    static public function convertToExtJSTree(array $node, $isInIteration = false, $adapter = '')
    {
        $parentKey = '';
        $children = array();

        if ($node['type'] == 'iteration') {
            $isInIteration = true;
            $adapter = $node['adapter'];
            $parentKey = $node['parentKey'];

            $icon = 'sprite-blue-folders-stack';
        } else if ($node['type'] == 'leaf') {
            $icon = 'sprite-icon_taskbar_top_inhalte_active';
        } else { // $node['type'] == 'node' 
            $icon = '';
        }

        // Get the attributes
        if (isset($node['attributes'])) {
            foreach ($node['attributes'] as $attribute) {
                $children[] = array(
                    'id' => $attribute['id'],
                    'text' => $attribute['name'],
                    'type' => $attribute['type'],
                    'index' => $attribute['index'],
                    'adapter' => $adapter,
                    'leaf' => true,
                    'expanded' => false,
                    'iconCls' => 'sprite-sticky-notes-pin',
                    'type' => 'attribute',
                    'swColumn' => $attribute['shopwareField'],
                    'inIteration' => $isInIteration
                );
            }
        }

        // Get the child nodes
        if (isset($node['children']) && count($node['children']) > 0) {
            foreach ($node['children'] as $child) {
                $children[] = static::convertToExtJSTree($child, $isInIteration, $adapter);
            }
        }

        return array(
            'id' => $node['id'],
            'type' => $node['type'],
            'index' => $node['index'],
            'text' => $node['name'],
            'adapter' => $adapter,
            'parentKey' => $parentKey,
            'leaf' => false,
            'expanded' => true,
            'iconCls' => $icon,
            'swColumn' => $node['shopwareField'],
            'inIteration' => $isInIteration,
            'children' => $children
        );
    }

    /**
     * Helper function which appends child node to the tree
     * 
     * @param array $child
     * @param array $node
     * @return boolean
     */
    static public function appendNode(array $child, array &$node)
    {
        if ($node['id'] == $child['parentId']) { // the parent node is found
            if ($child['type'] == 'attribute') {
                $node['attributes'][] = array(
                    'id' => $child['id'],
                    'type' => $child['type'],
                    'index' => $child['index'],
                    'name' => $child['text'],
                    'shopwareField' => $child['swColumn'],
                );
            } else if ($child['type'] == 'node') {
                $node['children'][] = array(
                    'id' => $child['id'],
                    'index' => $child['index'],
                    'type' => $child['type'],
                    'name' => $child['text'],
                    'shopwareField' => $child['swColumn'],
                );
            } else if ($child['type'] == 'iteration') {
                $node['children'][] = array(
                    'id' => $child['id'],
                    'name' => $child['text'],
                    'index' => $child['index'],
                    'type' => $child['type'],
                    'adapter' => $child['adapter'],
                    'parentKey' => $child['parentKey'],
                );
            } else {
                $node['children'][] = array(
                    'id' => $child['id'],
                    'type' => $child['type'],
                    'index' => $child['index'],
                    'name' => $child['text'],
                );
            }

            return true;
        } else {
            if (isset($node['children'])) {
                foreach ($node['children'] as &$childNode) {
                    if (static::appendNode($child, $childNode)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Helper function which finds node from the tree
     * 
     * @param string $id
     * @param array $node
     * @param string $parentId
     * @return boolean
     */
    static public function getNodeById($id, array $node, $parentId = 'root')
    {
        if ($node['id'] == $id) { // the node is found
            $node['parentId'] = $parentId;
            return $node;
        } else {
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as $attribute) {
                    $result = static::getNodeById($id, $attribute, $node['id']);
                    if ($result !== false) {
                        return $result;
                    }
                }
            }
            if (isset($node['children'])) {
                foreach ($node['children'] as $childNode) {
                    $result = static::getNodeById($id, $childNode, $node['id']);
                    if ($result !== false) {
                        return $result;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Helper function which appends child node to the tree
     * 
     * @param array $child
     * @param array $node
     * @return boolean
     */
    static public function moveNode(array $child, array &$node)
    {
        if ($node['id'] == $child['parentId']) { // the parent node is found
            if ($child['type'] == 'attribute') {
                unset($child['parentId']);
                $node['attributes'][] = $child;
            } else if ($child['type'] == 'node') {
                unset($child['parentId']);
                $node['children'][] = $child;
            } else {
                unset($child['parentId']);
                $node['children'][] = $child;
            }

            return true;
        } else {
            if (isset($node['children'])) {
                foreach ($node['children'] as &$childNode) {
                    if (static::moveNode($child, $childNode)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Helper function which finds and changes node from the tree
     * 
     * @param array $child
     * @param array $node
     * @return boolean
     */
    static public function changeNode(array $child, array &$node)
    {
        if ($node['id'] == $child['id']) { // the node is found
            $node['name'] = $child['text'];
            $node['type'] = $child['type'];
            $node['index'] = $child['index'];
            if (isset($child['swColumn'])) {
                $node['shopwareField'] = $child['swColumn'];
            } else {
                unset($node['shopwareField']);
            }

            if ($child['type'] == 'iteration') {
                if (isset($child['adapter'])) {
                    $node['adapter'] = $child['adapter'];
                } else {
                    unset($node['adapter']);
                }
                if (isset($child['parentKey'])) {
                    $node['parentKey'] = $child['parentKey'];
                } else {
                    unset($node['parentKey']);
                }
            }

            return true;
        } else {
            if (isset($node['children'])) {
                foreach ($node['children'] as &$childNode) {
                    if (static::changeNode($child, $childNode)) {
                        return true;
                    }
                }
            }
            if (isset($node['attributes'])) {
                foreach ($node['attributes'] as &$childNode) {
                    if (static::changeNode($child, $childNode)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Helper function which finds and deletes node from the tree
     * 
     * @param array $child
     * @param array $node
     * @return boolean
     */
    static public function deleteNode(array $child, array &$node)
    {
        if (isset($node['children'])) {
            foreach ($node['children'] as $key => &$childNode) {
                if ($childNode['id'] == $child['id']) {
                    unset($node['children'][$key]);
                    return true;
                } else if (static::deleteNode($child, $childNode)) {
                    return true;
                }
            }
        }
        if (isset($node['attributes'])) {
            foreach ($node['attributes'] as $key => &$childNode) {
                if ($childNode['id'] == $child['id']) {
                    unset($node['attributes'][$key]);
                    return true;
                }
            }
        }

        return false;
    }
    
    /**
     * Sorting tree via index key
     * 
     * @param array $node
     * @return array
     */
    static public function reorderTree($node)
    {
        $reorderdNode = array();
        if (is_array($node) && isset($node['children'])) {
            foreach ($node as $key => $value) {
                if ($key === 'children' || $key === 'attributes') {
                    foreach ($value as $innerValue) {
                        $value3 = self::reorderTree($innerValue);
                        $reorderdNode[$key][$innerValue['index']] = $value3;
                    }
                    ksort($reorderdNode[$key]);
                    
                } else {
                    $reorderdNode[$key] = $value;
                }
            }
        } else {
            $reorderdNode = $node;
        }
        
        return $reorderdNode;
    }

    /**
     * Returns the default tree for a profile by given profile type
     * 
     * @param string $profileType
     * @return string|boolean
     * @throws \Exception
     */
    static public function getDefaultTreeByProfileType($profileType)
    {
        switch ($profileType) {
            case 'categories':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Categories","index":1,"type":"node","children":[{"id":"537359399c90d","name":"Category","index":0,"type":"iteration","adapter":"default","attributes":null,"children":[{"id":"53e9f539a997d","type":"leaf","index":0,"name":"categoryID","shopwareField":"id"},{"id":"53e0a853f1b98","type":"leaf","index":1,"name":"parentID","shopwareField":"parentId"},{"id":"53e0cf5cad595","type":"leaf","index":2,"name":"description","shopwareField":"name"},{"id":"53e9f69bf2edb","type":"leaf","index":3,"name":"position","shopwareField":"position"},{"id":"53e0d1414b0d7","type":"leaf","index":4,"name":"metakeywords","shopwareField":"metaKeywords"},{"id":"53e0d17da1f06","type":"leaf","index":5,"name":"metadescription","shopwareField":"metaDescription"},{"id":"53e9f5c0eedaf","type":"leaf","index":6,"name":"cmsheadline","shopwareField":"cmsHeadline"},{"id":"53e9f5d80f10f","type":"leaf","index":7,"name":"cmstext","shopwareField":"cmsText"},{"id":"53e9f5e603ffe","type":"leaf","index":8,"name":"template","shopwareField":"template"},{"id":"53e9f5f87c87a","type":"leaf","index":9,"name":"active","shopwareField":"active"},{"id":"53e9f609c56eb","type":"leaf","index":10,"name":"blog","shopwareField":"blog"},{"id":"53e9f61981e83","type":"leaf","index":11,"name":"showfiltergroups","shopwareField":"showFilterGroups"},{"id":"53e9f62a03f55","type":"leaf","index":12,"name":"external","shopwareField":"external"},{"id":"53e9f637aa1fe","type":"leaf","index":13,"name":"hidefilter","shopwareField":"hideFilter"}]}]}]}';
            case 'articles':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"2","name":"Header","index":0,"type":"node","children":[{"id":"3","name":"HeaderChild","index":0,"type":"node"}]},{"id":"4","name":"Articles","index":1,"type":"","children":[{"id":"53e0d3148b0b2","name":"Article","index":0,"type":"iteration","adapter":"article","parentKey":"","shopwareField":"","children":{"0":{"id":"53e0d329364c4","type":"leaf","index":1,"name":"main_number","shopwareField":"mainNumber"},"1":{"id":"53e0d365881b7","type":"leaf","index":2,"name":"variant_number","shopwareField":"orderNumber"},"2":{"id":"53e0d3a201785","type":"leaf","index":3,"name":"title","shopwareField":"name"},"3":{"id":"53e0d3e46c923","type":"leaf","index":4,"name":"description","shopwareField":"description"},"4":{"id":"53e0d3fea6646","type":"leaf","index":5,"name":"supplier","shopwareField":"supplierNumber"},"5":{"id":"53e0d4333dca7","type":"leaf","index":6,"name":"tax","shopwareField":"tax"},"6":{"id":"53e0d44938a70","type":"","index":7,"name":"Prices","shopwareField":"","children":[{"id":"53e0d45110b1d","name":"Price","index":0,"type":"iteration","adapter":"price","parentKey":"variantId","shopwareField":"","children":[{"id":"53e0d472a0aa8","type":"leaf","index":1,"name":"price","shopwareField":"price"},{"id":"53e0d48a9313a","type":"leaf","index":2,"name":"pseudo_price","shopwareField":"pseudoPrice"}],"attributes":[{"id":"53e0d47e71009","type":"attribute","index":0,"name":"group","shopwareField":"priceGroup"}]}]},"8":{"id":"53e0d5f7d03d4","type":"","index":8,"name":"Configurators","shopwareField":"","children":[{"id":"53e0d603db6b9","name":"Configurator","index":0,"type":"iteration","adapter":"configurator","parentKey":"variantId","shopwareField":"","children":[{"id":"53e0d6142adca","type":"leaf","index":0,"name":"set","shopwareField":"configSetName"},{"id":"53e0d63477bef","type":"leaf","index":1,"name":"group","shopwareField":"configGroupName"},{"id":"53e0d6446940d","type":"leaf","index":2,"name":"option","shopwareField":"configOptionName"}],"attributes":[]}]}},"attributes":[{"id":"53e0d3ae3ea82","type":"attribute","index":0,"name":"inStock","shopwareField":"inStock"}]}],"shopwareField":""}]}';
            case 'articlesInStock':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"articlesInStock","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"article","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[],"children":[{"id":"5373865547d06","name":"article_number","index":0,"type":"leaf","shopwareField":"orderNumber"},{"id":"537386ac3302b","name":"Description","index":1,"type":"node","shopwareField":"description","attributes":[{"id":"53738718f26db","name":"supplier","index":0,"shopwareField":"supplier"}],"children":[{"id":"5373870d38c80","name":"Value","index":1,"type":"leaf","shopwareField":"additionalText"}]},{"id":"537388742e20e","name":"inStock","index":2,"type":"leaf","shopwareField":"inStock"}]}]}]}';
            case 'articlesPrices':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Prices","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Price","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[{"id":"53738653da10f","name":"articleDetailsId","index":0,"shopwareField":"articleDetailsId"}],"children":[{"id":"5373865547d06","name":"articleId","index":1,"type":"leaf","shopwareField":"articleId"},{"id":"537386ac3302b","name":"Description","index":2,"type":"node","shopwareField":"description","attributes":[{"id":"53738718f26db","name":"from","index":0,"shopwareField":"from"}],"children":[{"id":"5373870d38c80","name":"to","index":1,"type":"leaf","shopwareField":"to"}]},{"id":"537388742e20e","name":"price","index":3,"type":"leaf","shopwareField":"price"}]}]}]}';
            case 'articlesImages':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Images","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Images","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[{"id":"53738653da10f","name":"orderNumber","index":0,"shopwareField":"orderNumber","type":"attribute"}],"children":{"0":{"id":"5373865547d06","name":"image","index":1,"type":"leaf","shopwareField":"image"},"2":{"id":"537388742e20e","name":"main","index":2,"type":"leaf","shopwareField":"main"},"3":{"id":"53e39a5fddf41","type":"leaf","index":3,"name":"description","shopwareField":"description"},"4":{"id":"53e39a698522a","type":"leaf","index":4,"name":"position","shopwareField":"position"},"5":{"id":"53e39a737733d","type":"leaf","index":5,"name":"width","shopwareField":"width"},"6":{"id":"53e39a7c1a52e","type":"leaf","index":6,"name":"height","shopwareField":"height"}}}]}]}';
            case 'articlesTranslations':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Translations","index":1,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"Translation","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[{"id":"53738653da10f","name":"article_number","index":0,"shopwareField":"articleNumber"}],"children":{"2":{"id":"53ce5e8f25a24","name":"title","index":1,"type":"leaf","shopwareField":"title"},"3":{"id":"53ce5f9501db7","name":"description","index":2,"type":"leaf","shopwareField":"description"},"4":{"id":"53ce5fa3bd231","name":"long_description","index":3,"type":"leaf","shopwareField":"descriptionLong"},"5":{"id":"53ce5fb6d95d8","name":"keywords","index":4,"type":"leaf","shopwareField":"keywords"}}}]}]}';
            case 'orders':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"Orders","index":1,"type":"node","children":[{"id":"537359399c90d","name":"Order","index":0,"type":"iteration","adapter":"order","attributes":[{"id":"53738653da10f","name":"orderId","index":0,"shopwareField":"orderId","type":"attribute"}],"children":[{"id":"5373865547d06","name":"number","index":1,"type":"leaf","shopwareField":"number"},{"id":"537386ac3302b","name":"Billing","index":2,"type":"leaf","shopwareField":"description","attributes":[],"children":[{"id":"5373870d38c80","name":"firstname","index":0,"type":"leaf","shopwareField":"billingFirstname"},{"id":"53e35d64e7325","type":"leaf","index":1,"name":"lastname","shopwareField":"billingLastname"},{"id":"53e35d75a9896","type":"leaf","index":2,"name":"street","shopwareField":"billingStreet"},{"id":"53e35d8f98f25","type":"leaf","index":3,"name":"street_number","shopwareField":"billingStreetnumber"},{"id":"53e35da6a3478","type":"leaf","index":4,"name":"city","shopwareField":"billingCity"},{"id":"53e35dc552096","type":"leaf","index":5,"name":"zip_code","shopwareField":"billingZipcode"}]},{"id":"53e35f2357213","type":"","index":3,"name":"Aricles","shopwareField":"","children":[{"id":"53e35f359d696","name":"Article","index":0,"type":"iteration","adapter":"detail","parentKey":"orderId","shopwareField":"","children":[{"id":"53e35f4c45491","type":"leaf","index":0,"name":"name","shopwareField":"articleName"},{"id":"53e35f5e381e2","type":"leaf","index":1,"name":"price","shopwareField":"price"},{"id":"53e3602a81e1c","type":"leaf","index":2,"name":"quantity","shopwareField":"quantity"},{"id":"53e3603c76ac8","type":"leaf","index":3,"name":"tax","shopwareField":"tax"}]}]}]}]}]}';
            case 'customers':
                return '{"id":"root","name":"Root","type":"node","children":[{"id":"537359399c80a","name":"Header","index":0,"type":"node","children":[{"id":"537385ed7c799","name":"HeaderChild","index":0,"type":"node","shopwareField":""}]},{"id":"537359399c8b7","name":"customers","index":1,"type":"","shopwareField":"","children":[{"id":"53ea047e7dca5","name":"customer","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","children":[{"id":"53ea048def53f","type":"leaf","index":0,"name":"customernumber","shopwareField":"customerNumber"},{"id":"53ea052c8f4c9","type":"leaf","index":1,"name":"email","shopwareField":"email"},{"id":"53ea0535e3348","type":"leaf","index":2,"name":"password","shopwareField":"password"},{"id":"53ea054339f8e","type":"leaf","index":3,"name":"billing_company","shopwareField":"billingCompany"},{"id":"53ea057725a7d","type":"leaf","index":4,"name":"billing_department","shopwareField":"billingDepartment"},{"id":"53ea0595b1d31","type":"leaf","index":5,"name":"billing_salutation","shopwareField":"billingSalutation"},{"id":"53ea05dba6a4d","type":"leaf","index":6,"name":"billing_firstname","shopwareField":"billingFirstname"},{"id":"53ea05de1204b","type":"leaf","index":7,"name":"billing_lastname","shopwareField":"billingLastname"},{"id":"53ea05df9caf1","type":"leaf","index":8,"name":"billing_street","shopwareField":"billingStreet"},{"id":"53ea05e10ee03","type":"leaf","index":9,"name":"billing_streetnumber","shopwareField":"billingStreetnumber"},{"id":"53ea05e271edd","type":"leaf","index":10,"name":"billing_zipcode","shopwareField":"billingZipcode"},{"id":"53ea05e417656","type":"leaf","index":11,"name":"billing_city","shopwareField":"billingCity"},{"id":"53ea05e5e2e12","type":"leaf","index":12,"name":"phone","shopwareField":"billingPhone"},{"id":"53ea065093393","type":"leaf","index":13,"name":"fax","shopwareField":"billingFax"},{"id":"53ea0652597f1","type":"leaf","index":14,"name":"billing_countryID","shopwareField":"billingCountryID"},{"id":"53ea0653ddf4a","type":"leaf","index":15,"name":"billing_stateID","shopwareField":"billingStateID"},{"id":"53ea0691b1774","type":"leaf","index":16,"name":"ustid","shopwareField":"ustid"},{"id":"53ea069d37da6","type":"leaf","index":17,"name":"shipping_company","shopwareField":"shippingCompany"},{"id":"53ea069eac2c6","type":"leaf","index":18,"name":"shipping_department","shopwareField":"shippingDepartment"},{"id":"53ea06a0013c7","type":"leaf","index":19,"name":"shipping_salutation","shopwareField":"shippingSalutation"},{"id":"53ea06a23cdc1","type":"leaf","index":20,"name":"shipping_firstname","shopwareField":"shippingFirstname"},{"id":"53ea0e4a3792d","type":"leaf","index":21,"name":"shipping_lastname","shopwareField":"shippingLastname"},{"id":"53ea0e4fda6e7","type":"leaf","index":22,"name":"shipping_street","shopwareField":"shippingStreet"},{"id":"53ea0e52a578a","type":"leaf","index":23,"name":"shipping_streetnumber","shopwareField":"shippingStreetnumber"},{"id":"53ea0e55b2b31","type":"leaf","index":24,"name":"shipping_zipcode","shopwareField":"shippingZipcode"},{"id":"53ea0e57ddba7","type":"leaf","index":25,"name":"shipping_city","shopwareField":"shippingZipcode"},{"id":"53ea0e5a4ee0c","type":"leaf","index":26,"name":"shipping_countryID","shopwareField":"shippingCountryID"},{"id":"53ea0e5c6d67e","type":"leaf","index":27,"name":"paymentID","shopwareField":"paymentID"},{"id":"53ea0e5e88347","type":"leaf","index":28,"name":"newsletter","shopwareField":"newsletter"},{"id":"53ea0e6194ba6","type":"leaf","index":29,"name":"accountmode","shopwareField":"accountMode"},{"id":"53ea118664a90","type":"leaf","index":30,"name":"customergroup","shopwareField":"customergroup"},{"id":"53ea1188ca4ca","type":"leaf","index":31,"name":"language","shopwareField":"language"},{"id":"53ea118b67fe2","type":"leaf","index":32,"name":"subshopID","shopwareField":"subshopID"}]}]}]}';
            case 'newsletter':
                return '{"id":"root","name":"Root","type":"node","children":{"1":{"id":"537359399c8b7","name":"Users","index":0,"type":"node","shopwareField":"","children":[{"id":"537359399c90d","name":"user","index":0,"type":"iteration","adapter":"default","parentKey":"","shopwareField":"","attributes":[],"children":[{"id":"53e4b0f86aded","type":"leaf","index":0,"name":"email","shopwareField":"email"},{"id":"53e4b103bf001","type":"leaf","index":1,"name":"group","shopwareField":"groupName"},{"id":"53e4b105ea8c2","type":"leaf","index":2,"name":"salutation","shopwareField":"salutation"},{"id":"53e4b107872be","type":"leaf","index":3,"name":"firstname","shopwareField":"firstName"},{"id":"53e4b108d49f9","type":"leaf","index":4,"name":"lastname","shopwareField":"lastName"},{"id":"53e4b10a38e08","type":"leaf","index":5,"name":"street","shopwareField":"street"},{"id":"53e4b10c1d522","type":"leaf","index":6,"name":"streetnumber","shopwareField":"streetNumber"},{"id":"53e4b10d68c09","type":"leaf","index":7,"name":"zipcode","shopwareField":"zipCode"},{"id":"53e4b157416fc","type":"leaf","index":8,"name":"city","shopwareField":"city"},{"id":"53e4b1592dd4b","type":"leaf","index":9,"name":"lastmailing","shopwareField":"lastNewsletter"},{"id":"53e4b15a69651","type":"leaf","index":10,"name":"lastread","shopwareField":"lastRead"},{"id":"53e4b15bde918","type":"leaf","index":11,"name":"userID","shopwareField":"userID"}]}]}}}';
            default :
                throw new \Exception('The profile could not be created.');
        }

        return false;
    }

}