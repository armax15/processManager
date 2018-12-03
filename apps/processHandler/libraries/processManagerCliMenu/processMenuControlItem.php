<?php
namespace mpcmf\apps\processHandler\libraries\processManagerCliMenu;

use mpcmf\apps\processHandler\libraries\api\client\apiClient;
use mpcmf\apps\processHandler\libraries\cliMenu\controlItem;
use mpcmf\apps\processHandler\libraries\cliMenu\menu;
use mpcmf\apps\processHandler\libraries\cliMenu\menuItem;

class processMenuControlItem
    extends controlItem
{

    protected $processMethod;
    protected $expectedState;

    /**
     * processMenuControlItem constructor.
     *
     * @param        $keyboardEventNumber
     * @param        $buttonName
     * @param        $title
     * @param string $processMethod
     * @param string $expectedState
     */
    public function __construct($keyboardEventNumber, $buttonName, $title, $processMethod = 'start', $expectedState = 'running')
    {
        $this->keyboardEventNumber = $keyboardEventNumber;
        $this->buttonName = $buttonName;
        $this->title = $title;
        $this->processMethod = $processMethod;
        $this->expectedState = $expectedState;
    }

    /**
     * @param menuItem[]
     */
    public function execute(&$menu)
    {
        $this->actionOnSelectedItem($menu, $this);
    }

    protected  function actionOnSelectedItem (menu $serverListMenu, $menuControlItem)
    {
        $apiClient = apiClient::factory();
        $menuItems = $serverListMenu->getMenuItems();
        if (empty($menuItems)) {
            return;
        }
        $ids = [];
        foreach ($menuItems as $item) {
            if (!$item->isSelected()) {
                continue;
            }
            $ids[] = $item->getValue()['_id'];
        }
        if (empty($ids)) {
            $ids[] = $serverListMenu->getCurrentItem()->getValue()['_id'];
        }
        $result = $apiClient->call('process', $this->processMethod, ['ids' => $ids]);

        if (!$result['status']) {
            echo json_encode($result, 448);
            sleep(5);

            return;
        }


        $attempts = 20;
        do {
            $result = $apiClient->call('process', 'getByIds', ['ids' => $ids]);
            if (!$result['status']) {
                echo json_encode($result, 448);
                sleep(5);
            }
            $processes = $result['data'];
            $processedCount = 0;
            foreach ($processes as $process) {
                echo "{$process['name']} {$process['state']}\n";
                if ($process['state'] === $this->expectedState) {
                    $processedCount++;
                }
            }
            if (count($processes) === $processedCount) {
                echo "Successfully! \n";
                sleep(4);
                break;
            }
            sleep(1);
        } while ($attempts--);
    }
}