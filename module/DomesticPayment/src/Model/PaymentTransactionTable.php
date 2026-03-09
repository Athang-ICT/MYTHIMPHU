<?php
declare(strict_types=1);

namespace DomesticPayment\Model;

use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\ResultSet\ResultSet;
use DateTime;

class PaymentTransactionTable
{
    private TableGateway $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll()
    {
        return $this->tableGateway->select();
    }

    public function getPaymentTransaction(int $id): ?PaymentTransaction
    {
        $result = $this->tableGateway->select(['id' => $id]);
        if (!$result->count()) {
            return null;
        }
        return $result->current();
    }

    public function getByBfsTxnId(string $bfsTxnId): ?PaymentTransaction
    {
        $result = $this->tableGateway->select(['bfs_txn_id' => $bfsTxnId]);
        if (!$result->count()) {
            return null;
        }
        return $result->current();
    }

    public function savePaymentTransaction(PaymentTransaction $transaction): int
    {
        $data = [
            'merchant_id' => $transaction->getMerchantId(),
            'payment_desc' => $transaction->getPaymentDesc(),
            'txn_amount' => $transaction->getTxnAmount(),
            'remitter_acc_no' => $transaction->getRemitterAccNo(),
            'remitter_bank_id' => $transaction->getRemitterBankId(),
            'remitter_cid' => $transaction->getRemitterCid(),
            'bfs_txn_id' => $transaction->getBfsTxnId(),
            'status' => $transaction->getStatus(),
            'remitter_otp' => $transaction->getRemitterOtp(),
            'response_code' => $transaction->getResponseCode(),
            'response_message' => $transaction->getResponseMessage(),
            'created_at' => $transaction->getCreatedAt()->format('Y-m-d H:i:s'),
            'updated_at' => $transaction->getUpdatedAt()->format('Y-m-d H:i:s'),
            'notes' => $transaction->getNotes(),
        ];

        error_log("=== SAVING PAYMENT TRANSACTION ===");
        error_log("remitter_cid value being saved: " . ($data['remitter_cid'] ?? 'NULL'));
        error_log("Full data: " . json_encode($data));

        if ($transaction->getId()) {
            $this->tableGateway->update($data, ['id' => $transaction->getId()]);
            return $transaction->getId();
        } else {
            $this->tableGateway->insert($data);
            return (int) $this->tableGateway->getLastInsertValue();
        }
    }

    public function deletePaymentTransaction(int $id): void
    {
        $this->tableGateway->delete(['id' => $id]);
    }

    private function mapRowToPaymentTransaction(array $row): PaymentTransaction
    {
        $transaction = new PaymentTransaction();
        $transaction->setId((int)$row['id'])
            ->setMerchantId($row['merchant_id'])
            ->setPaymentDesc($row['payment_desc'])
            ->setTxnAmount($row['txn_amount'])
            ->setRemitterAccNo($row['remitter_acc_no'])
            ->setRemitterBankId($row['remitter_bank_id'])
            ->setBfsTxnId($row['bfs_txn_id'])
            ->setStatus($row['status'])
            ->setRemitterOtp($row['remitter_otp'])
            ->setResponseCode($row['response_code'])
            ->setResponseMessage($row['response_message'])
            ->setCreatedAt(new DateTime($row['created_at']))
            ->setUpdatedAt(new DateTime($row['updated_at']))
            ->setNotes($row['notes'] ?? null);

        return $transaction;
    }
}
