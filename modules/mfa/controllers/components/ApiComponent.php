<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

/** Component for api methods */
class Mfa_ApiComponent extends AppComponent
{
    /**
     * Helper function for verifying keys in an input array.
     *
     * @param array $keys
     * @param array $values
     * @throws Exception
     */
    private function _checkKeys($keys, $values)
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $values)) {
                throw new Exception('Parameter '.$key.' must be set.', -1);
            }
        }
    }

    /**
     * Submit your OTP after calling core login, and you will receive your api token.
     *
     * @param otp The one-time password
     * @param mfaTokenId The id of the temporary MFA token
     * @return The api token
     * @throws Exception
     */
    public function otpLogin($params)
    {
        $this->_checkKeys(array('otp', 'mfaTokenId'), $params);

        /** @var Mfa_ApitokenModel $tempTokenModel */
        $tempTokenModel = MidasLoader::loadModel('Apitoken', 'mfa');

        /** @var Mfa_OtpdeviceModel $otpDeviceModel */
        $otpDeviceModel = MidasLoader::loadModel('Otpdevice', 'mfa');

        /** @var TokenModel $apiTokenModel */
        $apiTokenModel = MidasLoader::loadModel('Token');

        $tempToken = $tempTokenModel->load($params['mfaTokenId']);
        if (!$tempToken) {
            throw new Exception('Invalid MFA token id', -1);
        }

        $apiToken = $apiTokenModel->load($tempToken->getTokenId());
        if (!$apiToken) {
            $tempTokenModel->delete($tempToken);
            throw new Exception('Corresponding api token no longer exists', -1);
        }
        $user = $tempToken->getUser();
        $otpDevice = $otpDeviceModel->getByUser($user);
        if (!$otpDevice) {
            $tempTokenModel->delete($tempToken);
            throw new Exception('User does not have an OTP device', -1);
        }
        $tempTokenModel->delete($tempToken);

        /** @var Mfa_OtpComponent $otpComponent */
        $otpComponent = MidasLoader::loadComponent('Otp', 'mfa');

        if (!$otpComponent->authenticate($otpDevice, $params['otp'])) {
            throw new Exception('Incorrect OTP', -1);
        }

        $token = $apiToken->getToken();

        return array('token' => $token);
    }
}
