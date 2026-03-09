<?php
declare(strict_types=1);

namespace DomesticPayment\Model;

use DateTime;

class PaymentTransaction
{
    private ?int $id = null;
    private string $merchantId;
    private string $paymentDesc;
    private string $txnAmount;
    private string $remitterAccNo;
    private string $remitterBankId;
    private ?string $remitterCid = null;
    private string $bfsTxnId;
    private string $status; // pending, authorized, inquired, completed, failed, cancelled
    private string $remitterOtp;
    private string $responseCode;
    private string $responseMessage;
    private DateTime $createdAt;
    private DateTime $updatedAt;
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function setMerchantId(string $merchantId): self
    {
        $this->merchantId = $merchantId;
        return $this;
    }

    public function getPaymentDesc(): string
    {
        return $this->paymentDesc;
    }

    public function setPaymentDesc(string $paymentDesc): self
    {
        $this->paymentDesc = $paymentDesc;
        return $this;
    }

    public function getTxnAmount(): string
    {
        return $this->txnAmount;
    }

    public function setTxnAmount(string $txnAmount): self
    {
        $this->txnAmount = $txnAmount;
        return $this;
    }

    public function getRemitterAccNo(): string
    {
        return $this->remitterAccNo;
    }

    public function setRemitterAccNo(string $remitterAccNo): self
    {
        $this->remitterAccNo = $remitterAccNo;
        return $this;
    }

    public function getRemitterBankId(): string
    {
        return $this->remitterBankId;
    }

    public function setRemitterBankId(string $remitterBankId): self
    {
        $this->remitterBankId = $remitterBankId;
        return $this;
    }

    public function getRemitterCid(): ?string
    {
        return $this->remitterCid;
    }

    public function setRemitterCid(string $remitterCid): self
    {
        $this->remitterCid = $remitterCid;
        return $this;
    }

    public function getBfsTxnId(): string
    {
        return $this->bfsTxnId;
    }

    public function setBfsTxnId(string $bfsTxnId): self
    {
        $this->bfsTxnId = $bfsTxnId;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getRemitterOtp(): string
    {
        return $this->remitterOtp;
    }

    public function setRemitterOtp(string $remitterOtp): self
    {
        $this->remitterOtp = $remitterOtp;
        return $this;
    }

    public function getResponseCode(): string
    {
        return $this->responseCode;
    }

    public function setResponseCode(string $responseCode): self
    {
        $this->responseCode = $responseCode;
        return $this;
    }

    public function getResponseMessage(): string
    {
        return $this->responseMessage;
    }

    public function setResponseMessage(string $responseMessage): self
    {
        $this->responseMessage = $responseMessage;
        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * Populate object from array (required by Laminas DB ResultSet)
     *
     * @param array $data
     * @return void
     */
    public function exchangeArray(array $data): void
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : 0;
        $this->merchantId = $data['merchant_id'] ?? '';
        $this->paymentDesc = $data['payment_desc'] ?? '';
        $this->txnAmount = $data['txn_amount'] ?? '';
        $this->remitterAccNo = $data['remitter_acc_no'] ?? '';
        $this->remitterBankId = $data['remitter_bank_id'] ?? '';
        $this->remitterCid = isset($data['remitter_cid']) && $data['remitter_cid'] !== null && $data['remitter_cid'] !== '' ? (string)$data['remitter_cid'] : null;
        $this->bfsTxnId = $data['bfs_txn_id'] ?? '';
        $this->status = $data['status'] ?? 'pending';
        $this->remitterOtp = $data['remitter_otp'] ?? '';
        $this->responseCode = $data['response_code'] ?? '';
        $this->responseMessage = $data['response_message'] ?? '';
        $this->createdAt = isset($data['created_at']) ? new DateTime($data['created_at']) : new DateTime();
        $this->updatedAt = isset($data['updated_at']) ? new DateTime($data['updated_at']) : new DateTime();
        $this->notes = $data['notes'] ?? null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'merchantId' => $this->merchantId,
            'paymentDesc' => $this->paymentDesc,
            'txnAmount' => $this->txnAmount,
            'remitterAccNo' => $this->remitterAccNo,
            'remitterBankId' => $this->remitterBankId,
            'remitter_cid' => $this->remitterCid,
            'bfsTxnId' => $this->bfsTxnId,
            'status' => $this->status,
            'remitterOtp' => $this->remitterOtp,
            'responseCode' => $this->responseCode,
            'responseMessage' => $this->responseMessage,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
        ];
    }
}
