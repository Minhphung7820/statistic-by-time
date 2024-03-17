<?php

namespace App\Module\CRM\Helpers;

use App\Module\CRM\Model\ConfigApprovalApprover;
use App\Module\CRM\Model\ConfigApprovalVote;
use App\Module\CRM\Model\ConfigApproveDiagram;
use App\Module\CRM\Model\Employee;
use App\Module\CRM\Model\Request\RequestGroupApprover;
use App\Module\CRM\Model\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Package\Exception\HttpException;

class SyncRequestGroupToConfigApproval
{
  public function handle()
  {
    try {
      return DB::transaction(function () {
        $groupsId = ConfigApproveDiagram::groupBy('request_group_id')->pluck('request_group_id')->toArray();
        $diagramsId = ConfigApproveDiagram::with(['approvers', 'fromStatus'])->whereIn('request_group_id', $groupsId)->select(['id', 'request_group_id', 'from_status'])->get()->toArray();
        foreach ($diagramsId as $diagramId) {
          $getApprovers = [];
          $getApproversCompare = $this->getRequestGroupApprover($diagramId['request_group_id']);
          if (!empty($diagramId['approvers'])) {
            $getApprovers = $diagramId['approvers'];
          }
          if (!$this->arraysEqual($getApproversCompare, $getApprovers)) {
            ConfigApprovalApprover::where('diagram_id', $diagramId['id'])->where('request_group_id', $diagramId['request_group_id'])->delete();
            if (isset($diagramId['from_status'])) {
              $fromStatus = $diagramId['from_status']['status'];
              $getTransactions = Transaction::where('status', $fromStatus)->pluck('id')->toArray();
              ConfigApprovalVote::where('current_status', $fromStatus)->whereIn('transaction_id', $getTransactions)->forceDelete();
            }
            $getApproversCompare = array_map(function ($item) use ($diagramId) {
              $item['diagram_id'] = $diagramId['id'];
              $item['created_at'] = now();
              unset($item['type_approve_text']);
              return $item;
            }, $getApproversCompare);
            if (!empty($getApproversCompare)) {
              foreach ($getApproversCompare as $data) {
                ConfigApprovalApprover::create([
                  'diagram_id' => $diagramId['id'] ?? null,
                  'root_id' => $data['root_id'] ?? null,
                  'order' => $data['order'] ?? null,
                  'request_group_id' => $data['request_group_id'] ?? null,
                  'weight' => $data['weight'] ?? null,
                  'user_id' => $data['user_id'] ?? null,
                  'type_approve' => $data['type_approve'] ?? null,
                  'created_at' => now()
                ]);
              }
            }
          }
          //
          //
          if (isset($diagramId['from_status'])) {
            $fromStatus = $diagramId['from_status']['status'];
            $getVotesByOrder = ConfigApprovalVote::where('diagram_id', $diagramId['id'])->where('current_status', $fromStatus)->orderBy('order', 'asc')->get()->toArray();
            foreach ($getVotesByOrder as $vote) {
              $transaction = Transaction::where('id', $vote['transaction_id'])->first();
              if (array_search($vote['root_id'], array_column($getApproversCompare, "root_id")) === false) {
                if ($transaction->status == $vote['current_status']) {
                  ConfigApprovalVote::where('id', $vote['id'])->forceDelete();
                }
              } else {
                $dataFind = $this->find($getApproversCompare, $vote['root_id'], "root_id");
                if ($dataFind && ($transaction->status == $vote['current_status'])) {
                  if ($dataFind['type_approve'] == 'fixed_approver') {
                    if ($dataFind['user_id'] != $vote['user_id']) {
                      ConfigApprovalVote::where('id', $vote['id'])->forceDelete();
                    }
                  }

                  if (isset($dataFind['group'])) {
                    if ($dataFind['group']['workflow'] == 'sequential') {
                      if ($dataFind['order']  != $vote['order']) {
                        ConfigApprovalVote::where('id', $vote['id'])->forceDelete();
                      }
                    }
                  }
                }
              }
            }
          }

          //
        }
      });
    } catch (\Exception $e) {
      throw new Exception($e->getMessage());
    }
  }

  private function find($array, $id, $filed = 'id')
  {
    if (!empty($array)) {
      foreach ($array as $key => $value) {
        if (isset($value[$filed])) {
          if ($value[$filed] == $id) {
            return $array[$key];
          }
        }
      }
    }

    return false;
  }

  private function getRequestGroupApprover($id)
  {
    $approvers = RequestGroupApprover::with(['group'])->where('request_group_id', $id)->where('type', 'approver')->orderBy('order', 'asc')->select(['id as root_id', 'weight', 'request_group_id', 'user_id', 'order', 'type_approve'])->get()->toArray();

    return $approvers;
  }

  private function arraysEqual($a, $b)
  {
    if (count($a) != count($b)) {
      return false;
    }

    foreach ($a as $item_a) {
      foreach ($b as $item_b) {
        if ($item_b['root_id'] == $item_a['root_id']) {
          if ($item_b['type_approve'] != $item_a['type_approve']) {
            return false;
          }

          if ($item_b['type_approve'] == 'fixed_approver') {
            if ($item_b['user_id'] != $item_a['user_id']) {
              return false;
            }
          }

          if (isset($item_a['group'])) {
            if ($item_a['group']['workflow'] == 'sequential') {
              if ($item_b['order'] != $item_a['order']) {
                return false;
              }
            }
          }
        }
      }
    }

    return true;
  }
}
