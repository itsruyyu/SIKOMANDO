<?php

namespace App\Policies;

use App\Enums\FieldSurveyStatus;
use App\Models\FieldSurvey;
use App\Models\Proposal;
use App\Models\User;

class FieldSurveyPolicy
{
    public function viewAny(User $user, ?Proposal $proposal = null): bool
    {
        if ($this->isPemohon($user)) {
            return false;
        }

        return $this->isAdministrator($user)
            || $this->isSurveyor($user)
            || $this->isAuditor($user)
            || $this->isApprover($user)
            || $this->isEvaluator($user)
            || $this->isVerifikator($user);
    }

    public function view(User $user, FieldSurvey $fieldSurvey): bool
    {
        if ($this->isPemohon($user)) {
            return false;
        }

        if ($this->isAdministrator($user)
            || $this->isAuditor($user)
            || $this->isApprover($user)
            || $this->isEvaluator($user)
            || $this->isVerifikator($user)) {
            return true;
        }

        return $this->isSurveyor($user)
            && (string) $fieldSurvey->surveyor_id === (string) $user->id;
    }

    public function create(User $user, Proposal $proposal): bool
    {
        return $this->isAdministrator($user);
    }

    public function updateSchedule(User $user, FieldSurvey $fieldSurvey): bool
    {
        if ($fieldSurvey->status?->isFinal()) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->isSurveyor($user)
            && (string) $fieldSurvey->surveyor_id === (string) $user->id;
    }

    public function start(User $user, FieldSurvey $fieldSurvey): bool
    {
        if ($fieldSurvey->status?->isFinal()) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->isSurveyor($user)
            && (string) $fieldSurvey->surveyor_id === (string) $user->id;
    }

    public function updateItem(User $user, FieldSurvey $fieldSurvey): bool
    {
        if ($fieldSurvey->status?->isFinal()) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->isSurveyor($user)
            && (string) $fieldSurvey->surveyor_id === (string) $user->id
            && $fieldSurvey->status?->canBeEditedBySurveyor();
    }

    public function addFinding(User $user, FieldSurvey $fieldSurvey): bool
    {
        return $this->updateItem($user, $fieldSurvey);
    }

    public function addDocument(User $user, FieldSurvey $fieldSurvey): bool
    {
        return $this->updateItem($user, $fieldSurvey);
    }

    public function fillResult(User $user, FieldSurvey $fieldSurvey): bool
    {
        return $this->updateItem($user, $fieldSurvey);
    }

    public function submit(User $user, FieldSurvey $fieldSurvey): bool
    {
        if (! in_array($fieldSurvey->status, [FieldSurveyStatus::IN_PROGRESS, FieldSurveyStatus::REVISION_REQUIRED], true)) {
            return false;
        }

        if ($this->isAdministrator($user)) {
            return true;
        }

        return $this->isSurveyor($user)
            && (string) $fieldSurvey->surveyor_id === (string) $user->id;
    }

    public function review(User $user, FieldSurvey $fieldSurvey): bool
    {
        // The surveyor who conducted the survey cannot review it themselves
        if ((string) $fieldSurvey->surveyor_id === (string) $user->id && ! $user->hasRole('SUPER_ADMIN')) {
            return false;
        }

        return $this->isAdministrator($user);
    }

    public function complete(User $user, FieldSurvey $fieldSurvey): bool
    {
        return $this->isAdministrator($user)
            || ($this->isSurveyor($user) && (string) $fieldSurvey->surveyor_id === (string) $user->id);
    }

    public function delete(User $user, FieldSurvey $fieldSurvey): bool
    {
        if ($fieldSurvey->status?->isFinal()) {
            return false;
        }

        return $this->isAdministrator($user);
    }

    private function isPemohon(User $user): bool
    {
        return $user->hasRole('PEMOHON') || $user->hasRole('pemohon');
    }

    private function isAdministrator(User $user): bool
    {
        return $user->hasAnyRole([
            'SUPER_ADMIN',
            'ADMIN_SIKOMANDO',
            'super_admin',
            'admin_sikomando',
        ]);
    }

    private function isSurveyor(User $user): bool
    {
        return $user->hasRole('SURVEYOR')
            || $user->hasRole('surveyor')
            || $user->hasPermission('proposal.survey');
    }

    private function isAuditor(User $user): bool
    {
        return $user->hasRole('AUDITOR')
            || $user->hasRole('auditor')
            || $user->hasPermission('audit.view');
    }

    private function isApprover(User $user): bool
    {
        return $user->hasRole('APPROVER')
            || $user->hasRole('approver')
            || $user->hasPermission('proposal.approve');
    }

    private function isEvaluator(User $user): bool
    {
        return $user->hasRole('EVALUATOR')
            || $user->hasRole('evaluator')
            || $user->hasPermission('proposal.evaluate');
    }

    private function isVerifikator(User $user): bool
    {
        return $user->hasRole('VERIFIKATOR')
            || $user->hasRole('verifikator')
            || $user->hasPermission('proposal.verify');
    }
}
