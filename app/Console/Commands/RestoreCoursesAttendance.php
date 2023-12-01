<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RestoreCoursesAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restore-courses-attendance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        try {
            DB::beginTransaction();
            // Найти все дубликаты занятий
            $originSessions = DB::select(
                "
                select distinct min(id) as id, min(start_time) as start_time, min(session_configuration_id) as session_configuration_id
                from sessions s
                group by start_time
            ");
            //Для каждого списка одинаковых занятий
            foreach ($originSessions as $originSession) {
                //Выбрать какое занятие является главным
                $duplicateSessions = DB::select("
                    select *
                    from sessions s
                    where (s.start_time = :start_time
                               AND s.session_configuration_id = :session_configuration_id
                    )",
                    [
                        'start_time' => $originSession->start_time,
                        'session_configuration_id'=>$originSession->session_configuration_id,
                    ]
                );

                $this->echoDuplicatesRange($originSession, $duplicateSessions);

                //Для каждого из дубликатов занятий
                foreach ($duplicateSessions as $duplicateSession) {

                    //Всех участников дубликата перенести в главное занятие
                    if ($duplicateSession->id <> $originSession->id) {
                        $duplicateMembers = DB::select('
                            select * from session_members sm
                            where sm.session_id = :id
                            ', ['id' => $duplicateSession->id]);
                        //Для каждого участника дубликата
                        foreach ($duplicateMembers as $duplicateMember) {
                            //Проверить, есть ли участник в главном занятии
                            $duplicateMemberInOriginSession = DB::select('
                                select * from session_members sm
                                where (sm.session_id = :origin_session_id
                                  AND sm.client_id = :duplicate_client_id)
                            ',[
                                'origin_session_id' => $originSession->id,
                                'duplicate_client_id' => $duplicateMember->client_id,
                            ]);
                            $fuck = DB::select('
                                select * from session_members sm');
                            // и если нет - перенести его туда
                            if (!$duplicateMemberInOriginSession) {
                                DB::insert("
                                    insert into session_members (session_id, client_id)
                                    values (:origin_session_id, :duplicate_client_id)
                                ", [
                                    'origin_session_id' => $originSession->id,
                                    'duplicate_client_id' => $duplicateMember->client_id,
                                ]);
                            }
                            //Удалить запись об участнике в занятии-дубликате
                            DB::delete("
                                delete from session_members
                                where id = :duplicate_member_id
                            ", [
                                'duplicate_member_id' => $duplicateMember->id,
                            ]);

                        }
                        //Удалить дубликат занятия
                        DB::delete( "
                        delete from sessions
                            where id = :duplicate_session_id
                        ",[
                            'duplicate_session_id' => $duplicateSession->id
                        ]);
                    }
                }
            }
            DB::commit();

            //Установить ограничение на уникальность в таблице занятия start_time и session_configuration_id
            DB::statement('alter table sessions add constraint check_session_exists unique (start_time, session_configuration_id)');
            //Установить ограничение на уникальность в таблице участника на session_id и client_id
            DB::statement('alter table session_members add constraint check_member_exists unique (session_id, client_id)');
        } catch (\Exception $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function echoDuplicatesRange($originSession, $duplicateSessions)
    {
        echo "НАБОР\n";
        echo "Оригинальная сессия: ";
        echo "$originSession->id : $originSession->start_time : $originSession->session_configuration_id \n";
        echo "Дубликаты\n";
        foreach ($duplicateSessions as $duplicateSession) {
            if ($duplicateSession->id <> $originSession->id) {
                echo "$duplicateSession->id : $duplicateSession->start_time : $duplicateSession->session_configuration_id \n";
            }
        }
    }
}
