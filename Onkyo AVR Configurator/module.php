<?php

declare(strict_types=1);
	class OnkyoAVRConfigurator extends IPSModule
	{
		public function Create()
		{
			//Never delete this line!
			parent::Create();

			$this->RequireParent('{CD39A489-D759-1786-1904-879A571231AF}');
			$this->SetReceiveDataFilter(".*NeverReceiveData.*");
		}

		public function Destroy()
		{
			//Never delete this line!
			parent::Destroy();
		}

		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
		}

		public function GetConfigurationForm() {
			$this->SendDebug(__FUNCTION__, 'Generating the form...', 0);

			$form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

			if (!$this->HasActiveParent()) {
				$form['actions'][] = [
					'name' => 'NoActiveParent',
					'type'  => 'PopupAlert',
					'popup' => [
						'items' => [
							[
								'type'    => 'Label',
								'caption' => 'This instance has no active parent(s).',
							]
						]
					]
				];
				
				return json_encode($form);
			}

			

		}
	}